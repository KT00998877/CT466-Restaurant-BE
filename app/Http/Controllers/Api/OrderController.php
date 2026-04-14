<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TableList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User; 
use App\Models\PointTransaction; 

class OrderController extends Controller
{ 
    public function index(Request $request)
    {
        // Lấy tất cả hoá đơn của user đang đăng nhập, kèm theo chi tiết món ăn (items)
        // Sắp xếp theo hoá đơn mới nhất (latest)
        $orders = Order::with('items')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    // ==========================================
    // CÁC HÀM DÀNH CHO ADMIN
    // ==========================================

    // Lấy toàn bộ danh sách hóa đơn
    public function adminIndex()
    {
        // Lấy tất cả hoá đơn, kèm theo chi tiết món ăn (items) và thông tin bàn (table)
        $orders = Order::with(['items.menuItem:id,name,price,img_url', 'table'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    // Tạo hóa đơn mới trực tiếp kèm món ăn (Dành cho Admin/Quản lý)
    public function adminStore(Request $request)
    {
        // 1. Validate dữ liệu hóa đơn VÀ danh sách món ăn
        $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'total_price'    => 'required|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|in:cod,banking,vnpay,momo',
            'payment_status' => 'required|in:paid,unpaid',
            'status'         => 'required|string',
            'notes'          => 'nullable|string',

            // Validate mảng món ăn gửi lên
            'items'            => 'required|array|min:1',
            'items.*.id'       => 'required|exists:menu_items,id',
            'items.*.name'     => 'required|string',
            'items.*.price'    => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.note'     => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // 2. Tạo Hóa đơn (Order)
            $order = Order::create([
                'user_id'          => $request->user()->id ?? null,
                'customer_name'    => $request->customer_name,
                'customer_phone'   => $request->customer_phone,
                'total_price'      => $request->total_price,
                'discount_amount'  => $request->discount_amount ?? 0,
                'payment_method'   => $request->payment_method,
                'payment_status'   => $request->payment_status,
                'status'           => $request->status,
                'notes'            => $request->notes,
                'table_id'         => null // Bắt buộc null vì đây là đơn mang đi
            ]);

            // 3. Lưu danh sách món ăn vào bảng OrderItem (Gửi xuống bếp)
            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id'     => $order->id,
                    'menu_item_id' => $item['id'],
                    'item_name'    => $item['name'],
                    'price'        => $item['price'],
                    'quantity'     => $item['quantity'],
                    'subtotal'     => $item['price'] * $item['quantity'],
                    'note'         => $item['note'] ?? null,
                    'status'       => 'pending' // Trạng thái bếp: Chờ nấu
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tạo hóa đơn tại quầy và gửi bếp thành công!',
                'data'    => $order->load('items') // Trả về order kèm theo các items vừa tạo
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi tạo hóa đơn: ' . $e->getMessage()
            ], 500);
        }
    }

    // Cập nhật trạng thái hóa đơn
    public function updateStatus(Request $request, $id)
    {
        // 1. Cập nhật các trạng thái chuẩn của HÓA ĐƠN (Order)
        $request->validate([
            'status' => 'required|in:pending,processing,completed,delivering,cancelled',
            'cancel_reason' => 'nullable|string'
        ]);

        try {
            // 2. Tìm Hóa đơn kèm theo danh sách các món ăn (items)
            $order = Order::with('items')->find($id);

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy hóa đơn!'], 404);
            }

            $oldStatus = $order->status;
            $newStatus = $request->status;

            // ==========================================
            // ---  KIỂM TRA MÓN ĂN KHI HOÀN THÀNH ĐƠN ---
            // ==========================================
            if ($newStatus === 'completed') {
                // Kiểm tra xem có món nào đang ở trạng thái 'pending' (chờ làm) hoặc 'cooking' (đang nấu) không
                $hasUnfinishedItems = $order->items->contains(function ($item) {
                    return in_array($item->status, ['pending', 'cooking']);
                });

                if ($hasUnfinishedItems) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể hoàn thành đơn! Tất cả các món phải được chế biến xong.'
                    ], 400); // Trả về lỗi 400 Bad Request
                }
            }
            // ==========================================

            // Cập nhật trạng thái mới
            $order->status = $newStatus;

            // NẾU ADMIN BẤM HỦY: Ghi lý do vào cột 'notes' của hóa đơn
            if ($newStatus === 'cancelled' && $request->filled('cancel_reason')) {
                $oldNote = $order->notes ? $order->notes . ' | ' : '';
                $order->notes = $oldNote . 'ADMIN HỦY: ' . $request->cancel_reason;
            }

            // ==========================================
            // LOGIC TÍCH ĐIỂM (KHI ĐƠN HÀNG HOÀN THÀNH)
            // ==========================================
            if ($oldStatus !== 'completed' && $newStatus === 'completed') {
                // Tính số điểm (Ví dụ: 10,000 VNĐ = 1 điểm)
                $pointsToEarn = floor($order->total_price / 10000);

                // Lưu số điểm đơn này mang lại vào database
                $order->points_earned = $pointsToEarn;

                // Tìm khách hàng (theo user_id nếu mua online, hoặc phone nếu mua tại quầy)
                $customer = null;
                if ($order->user_id && $order->user_id !== $request->user()->id) {
                    // Loại trừ trường hợp admin tự tạo đơn cho chính mình
                    $customer = User::find($order->user_id);
                } elseif ($order->customer_phone) {
                    $customer = User::where('phone', $order->customer_phone)
                        ->where('role', 'customer')
                        ->first();
                }

                // Cộng điểm cho khách
                if ($customer) {
                    $customer->points += $pointsToEarn;
                    $customer->save();

                    // Ghi lại giao dịch tích điểm vào bảng point_transactions
                    PointTransaction::create([
                        'user_id' => $customer->id,
                        'order_id' => $order->id,
                        'points' => $pointsToEarn,
                        'type' => 'earn', // Loại giao dịch: Tích điểm
                        'note' => 'Tích điểm từ đơn hàng #' . $order->id
                    ]);
                }
            }

            // ==========================================
            // LOGIC TRỪ ĐIỂM (KHI LỠ BẤM "HOÀN THÀNH" SAU ĐÓ ĐỔI THÀNH "HỦY")
            // ==========================================
            if ($oldStatus === 'completed' && $newStatus === 'cancelled') {
                if ($order->points_earned > 0) {
                    $customer = null;
                    if ($order->user_id && $order->user_id !== $request->user()->id) {
                        $customer = User::find($order->user_id);
                    } elseif ($order->customer_phone) {
                        $customer = User::where('phone', $order->customer_phone)
                            ->where('role', 'customer')
                            ->first();
                    }

                    // Trừ điểm (chỉ trừ nếu khách còn đủ điểm)
                    if ($customer && $customer->points >= $order->points_earned) {
                        $customer->points -= $order->points_earned;
                        $customer->save();

                        // Ghi lại giao dịch trừ điểm vào bảng point_transactions
                        PointTransaction::create([
                            'user_id' => $customer->id,
                            'order_id' => $order->id,
                            'points' => $order->points_earned,
                            'type' => 'deduct', // Loại giao dịch: Trừ điểm
                            'note' => 'Thu hồi điểm do hủy đơn hàng #' . $order->id
                        ]);
                    }

                    // Thu hồi điểm của hóa đơn này
                    $order->points_earned = 0;
                }
            }

            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái hóa đơn thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    // Cập nhật trạng thái thanh toán (cho admin)
    public function updatePaymentStatus(Request $request, $id)
    {
        $request->validate([
            'payment_status' => 'required|in:paid,unpaid'
        ]);

        try {
            $order = Order::find($id);

            if (!$order) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy hóa đơn!'], 404);
            }

            // Cập nhật trạng thái
            $order->payment_status = $request->payment_status;
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái thanh toán thành công!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    // Xóa hóa đơn
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy hóa đơn'], 404);
        }

        // Nếu bạn đã cài đặt cascade on delete ở Database thì các OrderItem sẽ tự mất
        // Nếu không, bạn cần xóa OrderItem trước: $order->items()->delete();
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa hóa đơn thành công!'
        ]);
    }


    // ==========================================
    // CÁC HÀM DÀNH CHO PHỤC VỤ (WAITER) - DINE IN
    // ==========================================


    // Tạo order mới (đặt món) tại bàn cho phục vụ
    public function store(Request $request)
    {
        // 1. Validate dữ liệu từ file Vue gửi lên
        $request->validate([
            'table_id' => 'required|exists:table_lists,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            // 2. Kiểm tra bàn này đã có Bill nào đang ăn (serving) chưa?
            $order = Order::where('table_id', $request->table_id)
                ->where('status', 'serving')
                ->first();

            // 3. Nếu chưa có thì tạo Hóa đơn mới
            if (!$order) {
                $order = Order::create([
                    'table_id' => $request->table_id,
                    'user_id' => $request->user()->id, // Lấy ID trực tiếp từ request
                    'total_price' => 0,
                    'status' => 'serving' // Trạng thái đang ăn tại bàn
                ]);

                // Đổi trạng thái bàn thành Đang có khách
                $table = TableList::find($request->table_id);
                if ($table) {
                    $table->update(['status' => 'occupied']);
                }
            }

            // 4. Lưu danh sách món ăn vào bảng order_items (để gửi xuống bếp)
            $totalAdditionalPrice = 0;

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $item['id'],
                    'item_name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity'],
                    'note' => $item['note'] ?? null,
                    'status' => 'pending' // Chờ bếp nấu
                ]);

                $totalAdditionalPrice += ($item['price'] * $item['quantity']);
            }

            // 5. Cộng dồn tiền vào tổng hóa đơn
            $order->total_price += $totalAdditionalPrice;
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã gửi order xuống bếp thành công!',
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Lỗi tạo order tại bàn: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi tạo order.'
            ], 500);
        }
    }

    // Hàm mở bàn trước khi gọi món cho phục vụ
    public function openTable(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:table_lists,id'
        ]);

        try {
            DB::beginTransaction();

            $table = TableList::find($request->table_id);

            // Kiểm tra xem bàn đã mở chưa
            if ($table->status === 'occupied') {
                return response()->json([
                    'success' => false,
                    'message' => 'Bàn này đang được phục vụ rồi.'
                ]);
            }

            // Tạo sẵn một Hóa đơn rỗng (chưa có món) gắn với bàn này
            $order = Order::create([
                'table_id' => $table->id,
                'user_id' => $request->user()->id,
                'total_price' => 0,
                'status' => 'serving'
            ]);

            // Cập nhật trạng thái bàn thành Đang có khách
            $table->update(['status' => 'occupied']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã mở bàn thành công!',
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi khi mở bàn: ' . $e->getMessage()
            ], 500);
        }
    }


    // Lấy Hóa đơn đang phục vụ của một Bàn cụ thể
    public function getActiveOrder($tableId)
    {
        $order = Order::with('items') // Lấy kèm các món đã gọi
            ->where('table_id', $tableId)
            ->where('status', 'serving')
            ->first();

        if ($order) {
            return response()->json([
                'success' => true,
                'order' => $order
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Không có hóa đơn nào đang phục vụ ở bàn này'
        ]);
    }

    // Thêm hàm cập nhật trạng thái món cho waiter 
    public function markItemAsServed($id)
    {
        try {
            $orderItem = OrderItem::find($id);

            if (!$orderItem) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn trong hệ thống!'], 404);
            }

            // Đổi trạng thái từ ready sang served
            $orderItem->status = 'served';
            $orderItem->save();

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    // Hủy bàn (dành cho phục vụ)
    public function cancelTable(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:table_lists,id'
        ]);

        DB::beginTransaction();
        try {
           
            $order = Order::where('table_id', $request->table_id)
                ->where('status', 'serving') 
                ->first();

            // Nếu có hóa đơn, đổi trạng thái hóa đơn thành 'cancelled' (Hủy)
            if ($order) {
                $order->status = 'cancelled';
                $order->notes = $order->notes . ' | Phục vụ đã hủy bàn';
                $order->save();
            }

            // Đổi trạng thái bàn thành trống
            $table = TableList::find($request->table_id);
            $table->status = 'available';
            $table->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đã hủy bàn thành công'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi server: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // CÁC HÀM BỔ SUNG CHO WAITER APP
    // ==========================================

    // 1. Đếm số món đã nấu xong (Cho Badge thông báo chuông đỏ)
    public function getReadyCount()
    {
        // Đếm các món 'ready' NHƯNG đơn hàng (order) phải có table_id (khách ngồi tại bàn)
        $count = OrderItem::where('status', 'ready')
            ->whereHas('order', function ($query) {
                $query->whereNotNull('table_id');
            })
            ->count();

        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    // 2. Lấy danh sách các món chờ bưng (Trang Trạng thái Bếp)
    public function getReadyItems()
    {
        // Phải dùng with('order.table') để lấy được tên bàn từ ID
        $items = OrderItem::with(['order.table'])
            ->where('status', 'ready')
            ->orderBy('updated_at', 'asc') // Món nào nấu xong trước thì ưu tiên hiển thị trước để bưng
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    // 3. Lấy danh sách hóa đơn trong ngày (Trang Đơn hàng / Lịch sử)
    
    public function getTodayOrders()
    {
        // 1. THÊM 'items' vào mảng with() để lấy kèm danh sách món ăn
        $orders = Order::with(['table', 'items'])
            ->whereDate('created_at', \Carbon\Carbon::today())
            ->latest()
            ->get();

        // 2. TÍNH LẠI TỔNG TIỀN THỰC TẾ
        $orders->each(function ($order) {
            if ($order->status === 'cancelled') {
                // Nếu cả bàn bị hủy, tiền chắc chắn = 0
                $order->total_price = 0;
            } else {
                // Nếu bàn đang phục vụ/đã thu tiền: Cộng dồn các món KHÔNG BỊ HỦY
                $order->total_price = $order->items
                    ->where('status', '!=', 'cancelled')
                    ->sum('subtotal');
            }
        });

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }
}