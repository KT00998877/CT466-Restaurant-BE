<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Models\TableList;
use App\Models\User;
use App\Models\PointTransaction;

class CheckoutController extends Controller
{
    // Hàm tính điểm (Ví dụ: 10,000 VNĐ = 1 điểm)
    private function calculatePoints($totalPrice)
    {
        return floor($totalPrice / 10000);
    }

    // ==========================================
    // KHÁCH HÀNG: THANH TOÁN ONLINE / GIAO HÀNG
    // ==========================================
    public function placeOrder(Request $request)
    {
        $user = $request->user();

        // 1. Lấy tất cả item trong giỏ hàng của user
        $cartItems = Cart::with('menuItem')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Giỏ hàng của bạn đang trống!'], 400);
        }

        // Validate thông tin giao hàng & số điểm sử dụng
        $request->validate([
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'customer_address' => 'required|string',
            'payment_method' => 'required|in:cod,vnpay',
            'points_used' => 'nullable|integer|min:0' // Khách hàng gửi số điểm muốn xài lên
        ]);

        DB::beginTransaction();

        try {
            // 2. Tính tổng tiền ban đầu
            $totalPrice = 0;
            foreach ($cartItems as $cart) {
                $totalPrice += $cart->menuItem->price * $cart->quantity;
            }

            // 3. LOGIC SỬ DỤNG ĐIỂM (1 ĐIỂM = 100 VNĐ)
            $pointsUsed = $request->points_used ?? 0;
            $discountAmount = 0;

            if ($pointsUsed > 0) {
                if ($pointsUsed > $user->points) {
                    return response()->json(['success' => false, 'message' => 'Bạn không đủ điểm để thanh toán!'], 400);
                }

                $discountAmount = $pointsUsed * 100;

                // Tránh giảm lố âm tiền (chỉ giảm tối đa bằng tổng bill)
                if ($discountAmount > $totalPrice) {
                    $discountAmount = $totalPrice;
                    $pointsUsed = ceil($discountAmount / 100);
                }

                // Trừ điểm của khách
                $user->points -= $pointsUsed;
                $user->save();
            }

            // Số tiền thực tế khách phải trả
            $finalPrice = $totalPrice - $discountAmount;

            // Ghi chú lịch sử xài điểm vào hóa đơn
            $orderNote = $request->notes;
            if ($pointsUsed > 0) {
                $orderNote = ($orderNote ? $orderNote . ' | ' : '') . "Đã dùng $pointsUsed điểm (Giảm " . number_format($discountAmount) . "đ)";
            }

            // (Lưu ý: Online thì pending nên khoan cộng điểm, để Admin bấm Hoàn thành mới cộng)
            $pointsToEarn = 0;

            // 4. Tạo Hóa đơn (Order)
            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $finalPrice, // LƯU GIÁ ĐÃ GIẢM
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'notes' => $orderNote,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'points_earned' => $pointsToEarn
            ]);

            // 5. Lưu Lịch sử TRỪ ĐIỂM (nếu có dùng)
            if ($pointsUsed > 0) {
                PointTransaction::create([
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'points' => $pointsUsed,
                    'type' => 'redeem',
                    'note' => 'Dùng điểm giảm giá cho đơn hàng online #' . $order->id
                ]);
            }

            // 6. Tạo Chi tiết hóa đơn (Order Items)
            foreach ($cartItems as $cart) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $cart->menu_item_id,
                    'item_name' => $cart->menuItem->name,
                    'price' => $cart->menuItem->price,
                    'quantity' => $cart->quantity,
                    'subtotal' => $cart->menuItem->price * $cart->quantity
                ]);
            }

            // 7. Xóa giỏ hàng
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công!',
                'order_id' => $order->id,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt hàng: ' . $e->getMessage()
            ], 500);
        }
    }


    // ==========================================
    // WAITER: THANH TOÁN TẠI BÀN (DINE IN)
    // ==========================================
    public function checkoutTable(Request $request)
    {
        $request->validate([
            'table_id' => 'required|exists:table_lists,id',
            'amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'note' => 'nullable|string',
            'customer_id' => 'nullable|exists:users,id', // SỬA Ở ĐÂY: Nhận customer_id từ Frontend
            'points_used' => 'nullable|integer|min:0'
        ]);

        DB::beginTransaction();

        try {
            $order = Order::where('table_id', $request->table_id)
                ->whereIn('status', ['serving'])
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bàn này không có hóa đơn nào cần thanh toán!'
                ], 404);
            }

            $customer = null;
            // SỬA Ở ĐÂY: Tìm khách hàng theo ID
            if ($request->customer_id) {
                $customer = User::find($request->customer_id);
            }

            // 1. LOGIC SỬ DỤNG ĐIỂM
            $pointsUsed = $request->points_used ?? 0;
            $discountAmount = 0;
            $finalAmount = $request->amount;

            if ($pointsUsed > 0) {
                if (!$customer) {
                    return response()->json(['success' => false, 'message' => 'Không tìm thấy khách hàng này để trừ điểm!'], 404);
                }
                if ($pointsUsed > $customer->points) {
                    return response()->json(['success' => false, 'message' => "Khách chỉ còn {$customer->points} điểm, không đủ để trừ!"], 400);
                }

                $discountAmount = $pointsUsed * 100;
                if ($discountAmount > $finalAmount) {
                    $discountAmount = $finalAmount;
                    $pointsUsed = ceil($discountAmount / 100);
                }

                $finalAmount = $finalAmount - $discountAmount;

                // Trừ điểm
                $customer->points -= $pointsUsed;
                $customer->save();

                // Lưu Lịch sử TRỪ ĐIỂM
                PointTransaction::create([
                    'user_id' => $customer->id,
                    'order_id' => $order->id,
                    'points' => $pointsUsed,
                    'type' => 'redeem',
                    'note' => 'Nhân viên thao tác trừ điểm thanh toán tại bàn #' . $order->id
                ]);
            }

            // 2. LOGIC TÍCH ĐIỂM TỪ HÓA ĐƠN NÀY
            // Đảm bảo bạn đã có hàm calculatePoints() trong Controller này
            $pointsToEarn = $this->calculatePoints($finalAmount);

            // Khai báo sẵn biến để lưu DB
            $pointsEarnedToSave = 0;

            if ($customer && $pointsToEarn > 0) {
                $customer->points += $pointsToEarn;
                $customer->save();

                $pointsEarnedToSave = $pointsToEarn;

                // Lưu Lịch sử CỘNG ĐIỂM
                PointTransaction::create([
                    'user_id' => $customer->id,
                    'order_id' => $order->id,
                    'points' => $pointsToEarn,
                    'type' => 'earn',
                    'note' => 'Tích điểm từ đơn hàng tại bàn #' . $order->id
                ]);

                $order->customer_phone = $customer->phone;
                $order->customer_name = $customer->name;
            }

            // Ghi chú đơn hàng
            $orderNotes = $request->note ? ($order->notes . ' | Thu ngân: ' . $request->note) : $order->notes;
            if ($pointsUsed > 0) {
                $orderNotes = $orderNotes . " | Đã dùng $pointsUsed điểm (Giảm " . number_format($discountAmount) . "đ)";
            }

            // 3. Cập nhật hóa đơn
            $order->update([
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => $request->payment_method,
                'total_price' => $finalAmount,
                'notes' => $orderNotes,
                'points_earned' => $pointsEarnedToSave
            ]);

            // 4. Đổi trạng thái bàn thành "Trống"
            $table = TableList::find($request->table_id);
            if ($table) {
                $table->status = 'available';
                $table->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thanh toán thành công! Đã dọn bàn.',
                'points_earned' => $pointsEarnedToSave,
                'discount' => $discountAmount,
                'final_amount' => $finalAmount
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thanh toán bàn: ' . $e->getMessage()
            ], 500);
        }
    }
}
