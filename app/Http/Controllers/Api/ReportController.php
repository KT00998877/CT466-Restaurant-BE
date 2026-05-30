<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DatabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    protected $dbService;

    public function __construct(DatabaseService $dbService)
    {
        $this->dbService = $dbService;
    }

    public function getRevenue(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'group_by'   => 'nullable|in:day,week,month',
        ]);

        $sets = $this->dbService->callProcedure(
            'CALL sp_thong_ke_doanh_thu(?, ?, ?)',
            [$request->start_date, $request->end_date, $request->group_by ?? 'day']
        );

        $summary = $sets[0][0] ?? null;

        return response()->json([
            'success' => true,
            'data'    => [
                'total_revenue' => $summary->tong_doanh_thu ?? 0,
                'total_orders'  => $summary->tong_don_hoan_thanh ?? 0,
                'avg_per_order' => $summary->trung_binh_moi_don ?? 0,
                'chart_data'    => $sets[1] ?? [],
            ]
        ]);
    }

    public function getMenuReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'top_n'      => 'nullable|integer|min:1|max:50',
        ]);

        // SỬA LỖI: Gọi qua $this->dbService
        $sets = $this->dbService->callProcedure(
            'CALL sp_thong_ke_mon_an(?, ?, ?)',
            [$request->start_date, $request->end_date, $request->top_n ?? 10]
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'top_items'       => $sets[0] ?? [],
                'top_by_revenue'  => $sets[1] ?? [],
                'by_category'     => $sets[2] ?? [],
            ]
        ]);
    }

    public function getDailySummary(Request $request)
    {
        $date = $request->date ?? now()->toDateString();
        $yesterday = Carbon::parse($date)->subDay()->toDateString();

        $sets = $this->dbService->callProcedure('CALL sp_thong_ke_hang_ngay(?)', [$date]);

        // Sử dụng Service để gọi Scalar Function
        $revenueYesterday = $this->dbService->callFunction('SELECT fn_doanh_thu_ngay(?) AS val', [$yesterday]);
        $ordersYesterday  = $this->dbService->callFunction('SELECT fn_so_don_ngay(?) AS val', [$yesterday]);

        return response()->json([
            'success' => true,
            'data'    => [
                'orders'         => $sets[0][0] ?? null,
                'reservations'   => $sets[1][0] ?? null,
                'top_menu_today' => $sets[2] ?? [],
                'pending_orders' => $sets[3] ?? [],
                'yesterday'      => [
                    'revenue' => $revenueYesterday ?? 0,
                    'orders'  => $ordersYesterday  ?? 0,
                ],
            ]
        ]);
    }

    public function getQuickStats()
    {
        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        $revenueToday     = $this->dbService->callFunction('SELECT fn_doanh_thu_ngay(?)', [$today]);
        $revenueYesterday = $this->dbService->callFunction('SELECT fn_doanh_thu_ngay(?)', [$yesterday]);
        $ordersToday      = $this->dbService->callFunction('SELECT fn_so_don_ngay(?)', [$today]);
        $revenueMonth     = $this->dbService->callFunction('SELECT fn_doanh_thu_thang(?, ?)', [now()->year, now()->month]);

        $growthPct = $revenueYesterday > 0
            ? round((($revenueToday - $revenueYesterday) / $revenueYesterday) * 100, 1)
            : null;

        return response()->json([
            'success' => true,
            'data'    => [
                'revenue_today'      => $revenueToday ?? 0,
                'revenue_growth_pct' => $growthPct,
                'orders_today'       => $ordersToday ?? 0,
                'revenue_month'      => $revenueMonth ?? 0,
            ]
        ]);
    }
    // -------------------------------------------------------
    // 2. Kho → CALL sp_thong_ke_kho
    // -------------------------------------------------------
    public function getInventoryReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $sets = $this->dbService->callProcedure(
            'CALL sp_thong_ke_kho(?, ?)',
            [$request->start_date, $request->end_date]
        );

        // [0]: tổng hợp → pivot thành labels/imports/exports cho Chart.js
        $summary = collect($sets[0] ?? []);
        $importRow = $summary->firstWhere('loai_giao_dich', 'import');
        $exportRow = $summary->firstWhere('loai_giao_dich', 'export');

        // [1]: chi tiết từng nguyên liệu → pivot cho biểu đồ theo ngày nếu cần
        $details = $sets[1] ?? [];

        // [2]: cảnh báo tồn kho thấp
        $lowStock = $sets[2] ?? [];

        // Tạo dữ liệu biểu đồ từ chi tiết (nhóm theo tên nguyên liệu, top 10 nhập nhiều nhất)
        $chartItems = collect($details)
            ->sortByDesc('tong_nhap')
            ->take(10);

        return response()->json([
            'success' => true,
            'data'    => [
                'labels'        => $chartItems->pluck('ten_nguyen_lieu')->values(),
                'imports'       => $chartItems->pluck('tien_nhap')->values(),
                'exports'       => $chartItems->pluck('tien_xuat')->values(),
                'total_import'  => $importRow->tong_gia_tri ?? 0,
                'total_export'  => $exportRow->tong_gia_tri ?? 0,
                'details'       => $details,
                'low_stock'     => $lowStock,
            ]
        ]);
    }

    // -------------------------------------------------------
    // 3. Đặt bàn → CALL sp_thong_ke_dat_ban
    // -------------------------------------------------------
    public function getReservationReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $sets = $this->dbService->callProcedure(
            'CALL sp_thong_ke_dat_ban(?, ?)',
            [$request->start_date, $request->end_date]
        );

        return response()->json([
            'success' => true,
            'data'    => [
                'by_status' => $sets[0] ?? [],   // doughnut chart
                'by_table'  => $sets[1] ?? [],   // top bàn
                'by_hour'   => $sets[2] ?? [],   // biểu đồ giờ
                'detail'    => $sets[3] ?? [],   // bảng danh sách
            ]
        ]);
    }
}
