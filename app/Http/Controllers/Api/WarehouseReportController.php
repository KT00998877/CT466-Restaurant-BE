<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WarehouseReport;
use App\Models\WarehouseReportItem;
use App\Models\Ingredient;
use Illuminate\Support\Facades\DB;

class WarehouseReportController extends Controller
{
    // 1. Tạo báo cáo mới + lưu danh sách items
    public function store(Request $request)
    {
        $validated = $request->validate([
            'report_date' => 'required|date|unique:warehouse_reports,report_date',
            'items' => 'required|array',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.opening_stock' => 'required|numeric|min:0',
            'items.*.import_quantity' => 'required|numeric|min:0',
            'items.*.export_quantity' => 'required|numeric|min:0',
            'items.*.closing_stock' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Tạo báo cáo mới
            $report = WarehouseReport::create([
                'report_date' => $validated['report_date'],
                'status' => 'saved',
                'created_by' => $request->user()->id ?? null,
            ]);

            // Lưu từng item vào báo cáo
            foreach ($validated['items'] as $item) {
                WarehouseReportItem::create([
                    'warehouse_report_id' => $report->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'opening_stock' => $item['opening_stock'],
                    'import_quantity' => $item['import_quantity'],
                    'export_quantity' => $item['export_quantity'],
                    'closing_stock' => $item['closing_stock'],
                    'note' => $item['note'] ?? null,
                ]);

                // Cập nhật tồn kho của nguyên liệu = closing_stock từ báo cáo
                $ingredient = Ingredient::find($item['ingredient_id']);
                if ($ingredient) {
                    $ingredient->update(['stock_quantity' => $item['closing_stock']]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Lưu báo cáo thành công!',
                'data' => [
                    'id' => $report->id,
                    'report' => $report,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi lưu báo cáo: ' . $e->getMessage()
            ], 500);
        }
    }

    // 2. Lấy báo cáo mới nhất (để tiếp tục chỉnh sửa)
    public function getLatest()
    {
        $report = WarehouseReport::where('status', 'draft')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Không có báo cáo nháp nào'
            ], 404);
        }

        $items = $report->items()
            ->with('ingredient:id,name,unit,stock_quantity')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'ingredient_id' => $item->ingredient_id,
                    'ingredient_name' => $item->ingredient->name,
                    'unit' => $item->ingredient->unit,
                    'opening_stock' => $item->opening_stock,
                    'import_quantity' => $item->import_quantity,
                    'export_quantity' => $item->export_quantity,
                    'closing_stock' => $item->closing_stock,
                    'note' => $item->note,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $report,
                'items' => $items,
            ]
        ]);
    }

    // 3. Lấy danh sách tất cả báo cáo (với phân trang)
    public function index(Request $request)
    {
        $reports = WarehouseReport::with('creator:id,name')
            ->orderBy('report_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    // 4. Xem chi tiết 1 báo cáo
    public function show($id)
    {
        $report = WarehouseReport::with('creator:id,name')
            ->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy báo cáo'
            ], 404);
        }

        $items = $report->items()
            ->with('ingredient:id,name,unit')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'ingredient_id' => $item->ingredient_id,
                    'ingredient_name' => $item->ingredient->name,
                    'unit' => $item->ingredient->unit,
                    'opening_stock' => $item->opening_stock,
                    'import_quantity' => $item->import_quantity,
                    'export_quantity' => $item->export_quantity,
                    'closing_stock' => $item->closing_stock,
                    'note' => $item->note,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'report' => $report,
                'items' => $items,
            ]
        ]);
    }

    // 5. Xóa báo cáo (chỉ xóa báo cáo nháp)
    public function destroy($id)
    {
        $report = WarehouseReport::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy báo cáo'
            ], 404);
        }

        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể xóa báo cáo đã lưu'
            ], 400);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xóa báo cáo thành công'
        ]);
    }

    // 6. Cập nhật báo cáo (chỉ update nếu còn draft)
    public function update(Request $request, $id)
    {
        $report = WarehouseReport::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy báo cáo'
            ], 404);
        }

        if ($report->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể cập nhật báo cáo đã lưu'
            ], 400);
        }

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.ingredient_id' => 'required|exists:ingredients,id',
            'items.*.opening_stock' => 'required|numeric|min:0',
            'items.*.import_quantity' => 'required|numeric|min:0',
            'items.*.export_quantity' => 'required|numeric|min:0',
            'items.*.closing_stock' => 'required|numeric|min:0',
            'items.*.note' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Xóa items cũ
            $report->items()->delete();

            // Thêm items mới
            foreach ($validated['items'] as $item) {
                WarehouseReportItem::create([
                    'warehouse_report_id' => $report->id,
                    'ingredient_id' => $item['ingredient_id'],
                    'opening_stock' => $item['opening_stock'],
                    'import_quantity' => $item['import_quantity'],
                    'export_quantity' => $item['export_quantity'],
                    'closing_stock' => $item['closing_stock'],
                    'note' => $item['note'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật báo cáo thành công!',
                'data' => $report
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi cập nhật báo cáo: ' . $e->getMessage()
            ], 500);
        }
    }
}
