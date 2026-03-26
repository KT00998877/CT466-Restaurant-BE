<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use App\Models\TableList;

class CheckoutController extends Controller
{
    public function placeOrder(Request $request)
    {
        $user = $request->user();

        // 1. Lấy tất cả item trong giỏ hàng của user
        $cartItems = Cart::with('menuItem')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Giỏ hàng của bạn đang trống!'], 400);
        }

        // Validate thông tin giao hàng
        $request->validate([
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'customer_address' => 'required|string',
            'payment_method' => 'required|in:cod,vnpay', // Thêm phương thức khác nếu cần
        ]);

        DB::beginTransaction();

        try {
            // 2. Tính tổng tiền
            $totalPrice = 0;
            foreach ($cartItems as $cart) {
                // Tính theo giá hiện tại của món ăn
                $totalPrice += $cart->menuItem->price * $cart->quantity;
            }

            // 3. Tạo Hóa đơn (Order)
            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $totalPrice,
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_address' => $request->customer_address,
                'notes' => $request->notes,
                'payment_method' => $request->payment_method,
                'status' => 'pending',
                'payment_status' => 'unpaid'
            ]);

            // 4. Tạo Chi tiết hóa đơn (Order Items)
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

            // 5. Xóa giỏ hàng sau khi đặt hàng thành công
            Cart::where('user_id', $user->id)->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Đặt hàng thành công!',
                'order_id' => $order->id
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đặt hàng: ' . $e->getMessage()
            ], 500);
        }
    }

    // HÀM MỚI DÀNH CHO WAITER THANH TOÁN TẠI BÀN
    public function checkoutTable(Request $request)
    {
        // 1. Chỉ validate những thứ Waiter gửi lên
        $request->validate([
            'table_id' => 'required|exists:table_lists,id',
            'amount' => 'required|numeric',
            'payment_method' => 'required|string',
            'note' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            // 2. Tìm Hóa đơn đang ăn của Bàn này 
            $order = Order::where('table_id', $request->table_id)
                ->whereIn('status', ['serving']) 
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bàn này không có hóa đơn nào cần thanh toán!'
                ], 404);
            }

            // 3. Cập nhật hóa đơn thành Đã thanh toán
            $order->update([
                'status' => 'completed', // Đã hoàn thành
                'payment_status' => 'paid', // Đã trả tiền
                'payment_method' => $request->payment_method,
                'total_price' => $request->amount,
                'notes' => $request->note ? ($order->notes . ' | Thu ngân: ' . $request->note) : $order->notes
            ]);

            // 4. Quan trọng nhất: Đổi trạng thái bàn thành "Trống"
            $table = TableList::find($request->table_id);
            $table->status = 'available';
            $table->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thanh toán thành công! Đã dọn bàn.'
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
