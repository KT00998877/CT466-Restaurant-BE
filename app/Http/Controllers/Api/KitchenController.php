<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OrderItem;

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
            ->limit(50) // Giới hạn 50 món gần nhất cho nhẹ web
            ->get();

        return response()->json(['success' => true, 'data' => $items]);
    }

    // 3. Cập nhật trạng thái món ăn (Bếp bấm Nấu / Xong)
    public function updateItemStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,cooking,ready,served,cancelled'
        ]);

        $item = OrderItem::find($id);
        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        $item->status = $request->status;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái!',
            'data' => $item
        ]);
    }
}
