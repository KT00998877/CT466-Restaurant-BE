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
        // Lấy danh sách bàn, KÈM THEO hóa đơn đang phục vụ (nếu có)
        $tables = TableList::with('activeOrder')
            ->orderBy('name', 'asc')
            ->get();

        // Chế biến lại dữ liệu trước khi gửi cho Vue
        $formattedTables = $tables->map(function ($table) {
            return [
                'id' => $table->id,
                'name' => $table->name,
                'capacity' => $table->capacity,
                'status' => $table->status,

                // Nếu bàn có hóa đơn đang ăn, lấy total_price. Nếu không có thì là 0.
                'currentTotal' => $table->activeOrder ? $table->activeOrder->total_price : 0,

                // (Tùy chọn) Lấy giờ khách vào bàn để hiển thị
                'timeSeated' => $table->activeOrder ? $table->activeOrder->created_at->format('H:i') : null,
            ];
        });

        // Trả về cho Vue
        return response()->json([
            'success' => true,
            'tables'  => $formattedTables // Phải dùng key 'tables' để khớp với code Vue cũ của bạn
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
