<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TableList;
use Illuminate\Http\Request;

class TableController extends Controller
{
    /**
     * Lấy danh sách tất cả các bàn kèm trạng thái
     */
    public function index()
    {
        // 1. SỬA CHỖ NÀY: Thêm '.items' để load kèm danh sách món ăn của hóa đơn
        $tables = TableList::with('activeOrder.items')
            ->orderBy('name', 'asc')
            ->get();

        // Chế biến lại dữ liệu trước khi gửi cho Vue
        $formattedTables = $tables->map(function ($table) {

            // 2. LOGIC TÍNH TIỀN MỚI
            $currentTotal = 0;
            if ($table->activeOrder) {
                // Chỉ tính tổng 'subtotal' của những món CÓ TRẠNG THÁI KHÁC 'cancelled'
                $currentTotal = $table->activeOrder->items
                    ->where('status', '!=', 'cancelled')
                    ->sum('subtotal');
            }

            return [
                'id' => $table->id,
                'name' => $table->name,
                'capacity' => $table->capacity,
                'status' => $table->status,

                // 3. Cập nhật lại biến currentTotal
                'currentTotal' => $currentTotal,

                // (Tùy chọn) Lấy giờ khách vào bàn để hiển thị
                'timeSeated' => $table->activeOrder ? $table->activeOrder->created_at->format('H:i') : null,
            ];
        });

        // Trả về cho Vue
        return response()->json([
            'success' => true,
            'tables'  => $formattedTables
        ]);
    }
    public function show($id)
    {
        $table = TableList::find($id);

        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bàn'
            ], 404);
        }

        // Trả về thông tin bàn nếu tìm thấy
        return response()->json($table); // Hoặc json(['data' => $table]) tùy cấu trúc của bạn
    }
}
