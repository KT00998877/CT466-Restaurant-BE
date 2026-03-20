<?php

namespace App\Http\Controllers\Api;

use App\Models\Cart;
use App\Models\MenuItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    /**
     * Lấy danh sách giỏ hàng của user hiện tại
     */
    public function index(Request $request): JsonResponse
    {
        $cartItems = Cart::with('menuItem')
            ->where('user_id', $request->user()->id)
            ->get()
            ->map(function ($cart) {
                return [
                    'id'           => $cart->id,
                    'menu_item_id' => $cart->menu_item_id,
                    'name'         => $cart->menuItem->name,
                    'img_url'      => $cart->menuItem->img_url,
                    'quantity'     => $cart->quantity,
                    'price'        => $cart->price,
                    'subtotal'     => $cart->price * $cart->quantity,
                ];
            });

        $total = $cartItems->sum('subtotal');

        return response()->json([
            'success' => true,
            'data'    => $cartItems,
            'total'   => $total,
        ]);
    }

    /**
     * Thêm món vào giỏ hàng
     * Nếu món đã có → cộng thêm số lượng
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'quantity'     => ['required', 'integer', 'min:1'],
        ]);

        $menuItem = MenuItem::findOrFail($request->menu_item_id);

        $cartItem = Cart::where('user_id', $request->user()->id)
            ->where('menu_item_id', $menuItem->id)
            ->first();

        if ($cartItem) {
            // Món đã có trong giỏ → cộng thêm số lượng
            $cartItem->increment('quantity', $request->quantity);
        } else {
            // Thêm mới
            $cartItem = Cart::create([
                'user_id'      => $request->user()->id,
                'menu_item_id' => $menuItem->id,
                'quantity'     => $request->quantity,
                'price'        => $menuItem->price, // Lưu giá tại thời điểm thêm
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng!',
            'data'    => $cartItem,
        ], 201);
    }

    /**
     * Cập nhật số lượng món trong giỏ
     */
    public function update(Request $request, Cart $cart): JsonResponse
    {
        // Chỉ cho phép sửa giỏ của chính mình
        if ($cart->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Không có quyền.'], 403);
        }

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart->update(['quantity' => $request->quantity]);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật số lượng.',
            'data'    => $cart,
        ]);
    }

    /**
     * Xóa một món khỏi giỏ
     */
    public function destroy(Request $request, Cart $cart): JsonResponse
    {
        if ($cart->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Không có quyền.'], 403);
        }

        $cart->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa món khỏi giỏ hàng.',
        ]);
    }

    /**
     * Xóa toàn bộ giỏ hàng của user
     */
    public function clear(Request $request): JsonResponse
    {
        Cart::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa toàn bộ giỏ hàng.',
        ]);
    }
}