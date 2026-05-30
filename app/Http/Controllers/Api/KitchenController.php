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
            'status' => 'required|in:pending,cooking,ready,served,cancelled',
            'used_ingredients' => 'nullable|array', // Hứng mảng nguyên liệu từ giao diện Vue
            'used_ingredients.*.ingredient_id' => 'required|integer',
            'used_ingredients.*.quantity' => 'required|numeric|min:0'
        ]);

        // Eager load để tối ưu truy vấn
        $item = OrderItem::with('menuItem.ingredients')->find($id);

        if (!$item) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        $oldStatus = $item->status;
        $newStatus = $request->status;

        // LOGIC TRỪ TỒN KHO: Khi bếp bấm "Xác nhận nấu" (chuyển sang cooking)
        // Và tránh trừ đúp nếu món đã ở trạng thái cooking/ready từ trước
        if ($newStatus === 'cooking' && !in_array($oldStatus, ['cooking', 'ready', 'served'])) {
            DB::beginTransaction();
            try {
                $usedIngredients = [];

                // Trường hợp 1: Có dữ liệu nguyên liệu thực tế từ giao diện Bếp gửi lên
                if ($request->has('used_ingredients') && !empty($request->used_ingredients)) {
                    foreach ($request->used_ingredients as $ing) {
                        $ingredient = DB::table('ingredients')->where('id', $ing['ingredient_id'])->first();

                        // Kiểm tra kho có đủ không
                        if (!$ingredient) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Nguyên liệu không tồn tại: #' . $ing['ingredient_id']
                            ], 400);
                        }

                        if ($ingredient->stock_quantity < $ing['quantity']) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Kho không đủ ' . $ingredient->name . ': cần ' . $ing['quantity'] . ', kho còn ' . $ingredient->stock_quantity
                            ], 400);
                        }

                        DB::table('ingredients')
                            ->where('id', $ing['ingredient_id'])
                            ->decrement('stock_quantity', $ing['quantity']);

                        // Lưu nguyên liệu đã dùng để hoàn tác sau
                        $usedIngredients[] = [
                            'ingredient_id' => $ing['ingredient_id'],
                            'ingredient_name' => $ingredient->name,
                            'quantity' => $ing['quantity'],
                            'unit' => $ingredient->unit
                        ];
                    }
                }
                // Trường hợp 2: Fallback (Nếu request không gửi mảng nguyên liệu, tự trừ theo công thức gốc)
                else {
                    if ($item->menuItem && $item->menuItem->ingredients) {
                        foreach ($item->menuItem->ingredients as $ingredient) {
                            $totalDeduct = $ingredient->pivot->quantity_required * $item->quantity;

                            // Kiểm tra kho có đủ không
                            if ($ingredient->stock_quantity < $totalDeduct) {
                                DB::rollBack();
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Kho không đủ: ' . $ingredient->name
                                ], 400);
                            }

                            $ingredient->stock_quantity -= $totalDeduct;
                            $ingredient->save();

                            // Lưu nguyên liệu đã dùng để hoàn tác sau
                            $usedIngredients[] = [
                                'ingredient_id' => $ingredient->id,
                                'ingredient_name' => $ingredient->name,
                                'quantity' => $totalDeduct,
                                'unit' => $ingredient->unit
                            ];
                        }
                    }
                }

                // Cập nhật trạng thái và lưu dữ liệu nguyên liệu đã dùng
                $item->status = $newStatus;
                $item->used_ingredients = $usedIngredients;
                $item->save();

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Bắt đầu nấu và đã trừ kho!']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi trừ tồn kho: ' . $e->getMessage()
                ], 500);
            }
        }

        // ✅ HOÀN TÁC: Chuyển từ cooking → pending (hoàn lại kho)
        if ($newStatus === 'pending' && $oldStatus === 'cooking') {
            DB::beginTransaction();
            try {
                // Hoàn lại kho từ dữ liệu đã lưu
                if ($item->used_ingredients && is_array($item->used_ingredients)) {
                    foreach ($item->used_ingredients as $usedIng) {
                        DB::table('ingredients')
                            ->where('id', $usedIng['ingredient_id'])
                            ->increment('stock_quantity', $usedIng['quantity']);
                    }
                }

                // Xóa dữ liệu nguyên liệu đã dùng
                $item->status = $newStatus;
                $item->used_ingredients = null;
                $item->save();

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Đã hoàn tác và hoàn lại kho!']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi hoàn tác: ' . $e->getMessage()
                ], 500);
            }
        }

        // NẾU BẾP HỦY MÓN
        if ($newStatus === 'cancelled') {
            // Ghi nhận lý do hủy (nếu bảng có cột cancel_reason)
            if ($request->has('cancel_reason')) {
                // $item->cancel_reason = $request->cancel_reason;
            }
        }

        // CÁC TRẠNG THÁI BÌNH THƯỜNG KHÁC (ready, served, cancelled...)
        $item->status = $newStatus;
        $item->save();

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật trạng thái!',
            'data' => $item
        ]);
    }

    // 4. Lấy danh sách tất cả nguyên liệu trong kho (cho Select box)
    public function getAllIngredients()
    {
        $ingredients = DB::table('ingredients')->select('id', 'name', 'unit', 'stock_quantity')->get();
        return response()->json(['success' => true, 'data' => $ingredients]);
    }

    // 5. Lấy danh sách nguyên liệu cần chuẩn bị cho món đang nấu
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
