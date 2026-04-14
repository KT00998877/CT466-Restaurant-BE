<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    // 1. Lấy danh sách các món ĐANG CHỜ hoặc ĐANG NẤU
    public function getPendingItems()
    {
        // Lấy các món có status pending/cooking, nạp kèm thông tin Order và Bàn
        $items = OrderItem::with('order.table')
            ->whereIn('status', ['pending', 'cooking'])
            ->orderBy('created_at', 'asc') // Ưu tiên món gọi trước (cũ nhất) lên đầu
            ->get();
        return response()->json(['success' => true, 'data' => $items]);
    }

    // 2. Lấy danh sách các món ĐÃ XONG (Lịch sử)
    public function getHistoryItems()
    {
        $items = OrderItem::with('order.table')
            ->whereIn('status', ['ready', 'served'])
            ->orderBy('updated_at', 'desc') // Món mới xong lên đầu
            ->limit(50) // Giới hạn 50 món gần nhất 
            ->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    // 3. Cập nhật trạng thái món ăn (Bếp bấm Nấu / Xong)
    public function updateItemStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,cooking,ready,served,cancelled'
        ]);

        // Eager load luôn menuItem và ingredients để tối ưu truy vấn
        $item = OrderItem::with('menuItem.ingredients')->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        // Logic trừ tồn kho: Chỉ trừ khi trạng thái MỚI là 'ready' 
        // và trạng thái CŨ chưa phải là 'ready' hoặc 'served' (tránh trừ đúp 2 lần)
        if ($request->status === 'ready' && !in_array($item->status, ['ready', 'served'])) {
            DB::beginTransaction();
            try {
                // 1. Duyệt qua công thức món ăn để trừ nguyên liệu
                if ($item->menuItem && $item->menuItem->ingredients) {
                    foreach ($item->menuItem->ingredients as $ingredient) {
                        // Công thức tính: Định mức 1 món * Số lượng khách gọi
                        // Giả định bảng order_items của bạn có cột 'quantity'
                        $totalDeduct = $ingredient->pivot->quantity_required * $item->quantity;

                        // Trừ tồn kho (Có thể thêm logic kiểm tra kho âm ở đây nếu cần)
                        $ingredient->stock_quantity -= $totalDeduct;
                        $ingredient->save();
                    }
                }

                // 2. Cập nhật trạng thái món
                $item->status = $request->status;
                $item->save();

                DB::commit(); // Xác nhận lưu toàn bộ thay đổi
            } catch (\Exception $e) {
                DB::rollBack(); // Hủy bỏ thao tác nếu có lỗi
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi trừ tồn kho: ' . $e->getMessage()
                ], 500);
            }
        } else {
            // Nếu là các trạng thái khác (pending, cooking, cancelled), chỉ cập nhật bình thường
            $item->status = $request->status;
            $item->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái!',
            'data' => $item
        ]);
    }

    // 4. Lấy danh sách nguyên liệu cần chuẩn bị cho món đang nấu
    public function getItemIngredients($id)
    {
        $item = OrderItem::with('menuItem.ingredients')->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món'], 404);
        }

        $ingredients = [];
        if ($item->menuItem && $item->menuItem->ingredients) {
            foreach ($item->menuItem->ingredients as $ingredient) {
                $ingredients[] = [
                    'id'                => $ingredient->id,
                    'name'              => $ingredient->name,
                    'unit'              => $ingredient->unit,
                    'stock_quantity'    => $ingredient->stock_quantity,
                    'quantity_required' => $ingredient->pivot->quantity_required * $item->quantity,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'item_name'   => $item->item_name,
                'quantity'    => $item->quantity,
                'ingredients' => $ingredients,
            ]
        ]);
    }
}
