<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;
use App\Models\IngredientTransaction;

class IngredientController extends Controller
{
    // 1. Lấy danh sách toàn bộ nguyên liệu (có thể kết hợp tìm kiếm)
    public function index(Request $request)
    {
        $query = Ingredient::query();

        // Nếu admin muốn tìm kiếm theo tên
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $ingredients = $query->orderBy('name', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $ingredients
        ]);
    }

    // 2. Cảnh báo kho: Lấy danh sách nguyên liệu sắp hết (tồn kho <= mức cảnh báo)
    public function getLowStock()
    {
        $lowStockIngredients = Ingredient::whereColumn('stock_quantity', '<=', 'reorder_level')
            ->orderBy('stock_quantity', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $lowStockIngredients
        ]);
    }

    // 3. Thêm mới một nguyên liệu vào kho
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:ingredients,name',
            'unit' => 'required|string|max:50',
            'stock_quantity' => 'numeric|min:0',
            'reorder_level' => 'numeric|min:0',
        ]);

        $ingredient = Ingredient::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm nguyên liệu mới thành công.',
            'data' => $ingredient
        ], 201);
    }

    // 4. Xem chi tiết một nguyên liệu (kèm theo các món ăn đang sử dụng nó)
    public function show($id)
    {
        $ingredient = Ingredient::with('menuItems')->find($id);

        if (!$ingredient) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nguyên liệu'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ingredient
        ]);
    }

    // 5. Cập nhật thông tin nguyên liệu (hoặc dùng để Admin tự nhập thêm kho)
    public function update(Request $request, $id)
    {
        $ingredient = Ingredient::find($id);

        if (!$ingredient) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nguyên liệu'], 404);
        }

        $validated = $request->validate([
            // Bỏ qua check unique cho chính ID hiện tại
            'name' => 'sometimes|required|string|max:255|unique:ingredients,name,' . $id,
            'unit' => 'sometimes|required|string|max:50',
            'stock_quantity' => 'sometimes|numeric|min:0',
            'reorder_level' => 'sometimes|numeric|min:0',
        ]);

        $ingredient->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật nguyên liệu.',
            'data' => $ingredient
        ]);
    }

    // 6. Xóa nguyên liệu
    public function destroy($id)
    {
        $ingredient = Ingredient::find($id);

        if (!$ingredient) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy nguyên liệu'], 404);
        }

        // Do trong Migration chúng ta đã set onDelete('cascade') ở bảng trung gian,
        // nên khi xóa nguyên liệu, các công thức chứa nó trong ingredient_menu_item cũng sẽ tự động bị xóa.
        $ingredient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa nguyên liệu thành công.'
        ]);
    }

    // 7. Xử lý Nhập / Xuất kho
    // 7. Xử lý Nhập / Xuất kho
    public function handleTransaction(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:import,export',
            'quantity' => 'required|numeric|min:0.01',
            'price' => 'nullable|numeric|min:0', // ---> BỔ SUNG: Cho phép truyền giá mới khi nhập hàng
            'note' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            // Dùng lockForUpdate() để chặn các luồng khác thay đổi kho cùng lúc
            $ingredient = Ingredient::lockForUpdate()->find($id);

            if (!$ingredient) {
                return response()->json(['success' => false, 'message' => 'Không tìm thấy nguyên liệu'], 404);
            }

            // Kiểm tra điều kiện xuất kho
            if ($request->type === 'export' && $ingredient->stock_quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tồn kho không đủ để xuất! Hiện tại chỉ còn ' . $ingredient->stock_quantity . ' ' . $ingredient->unit
                ], 400);
            }

            // ---> XỬ LÝ LỘ TRÌNH GIÁ TIỀN
            // Lấy giá từ request (nếu có), không thì lấy giá mặc định của nguyên liệu
            $unitPrice = $request->price ?? $ingredient->price;
            $totalPrice = $unitPrice * $request->quantity;

            // Tính toán số lượng mới
            if ($request->type === 'import') {
                $ingredient->stock_quantity += $request->quantity;

                // NẾU LÀ NHẬP HÀNG: Cập nhật luôn giá mới nhất làm giá tham khảo cho nguyên liệu
                if ($request->has('price')) {
                    $ingredient->price = $unitPrice;
                }
            } else {
                $ingredient->stock_quantity -= $request->quantity;
            }

            $ingredient->save();

            // Ghi lại lịch sử giao dịch (ĐÃ BỔ SUNG CỘT TIỀN)
            $transaction = IngredientTransaction::create([
                'ingredient_id' => $ingredient->id,
                'user_id' => $request->user()->id ?? null,
                'type' => $request->type,
                'quantity' => $request->quantity,
                'unit_price' => $unitPrice,       
                'total_price' => $totalPrice,     
                'stock_after' => $ingredient->stock_quantity,
                'note' => $request->note
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->type === 'import' ? 'Đã nhập kho thành công.' : 'Đã xuất kho thành công.',
                'current_stock' => $ingredient->stock_quantity,
                'transaction' => $transaction
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
            ], 500);
        }
    }

    // 8. Xem lịch sử Nhập/Xuất của một nguyên liệu cụ thể
    public function getTransactions($id)
    {
        $transactions = IngredientTransaction::where('ingredient_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }
}
