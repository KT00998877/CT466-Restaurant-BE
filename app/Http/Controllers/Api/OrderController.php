<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TableList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


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
        // Chỉ cần load chi tiết món ăn. Thông tin khách đã có sẵn trong bảng orders.
        $orders = Order::with(['items.menuItem:id,name,price,img_url'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    // Cập nhật trạng thái hóa đơn
    public function updateStatus(Request $request, $id)
    {
        // THÊM 'delivering' VÀO ĐÂY
        $request->validate([
            'status' => 'required|string|in:pending,processing,delivering,completed,cancelled'
        ]);

        $order = Order::find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy hóa đơn'], 404);
        }

        $order->status = $request->status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái đơn hàng!',
            'data' => $order
        ]);
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


    // Tạo order mới (đặt món) tại bàn
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

    // Hàm mở bàn trước khi gọi món
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
}