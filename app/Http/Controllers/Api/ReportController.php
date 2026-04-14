<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Order;
use App\Models\IngredientTransaction; // THÊM DÒNG NÀY ĐỂ GỌI MODEL
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    public function getRevenue(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
        $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();

        // Tính khoảng cách ngày
        $diffInDays = $startDate->diffInDays($endDate);

        $totalRevenue = Order::where('status', 'completed')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('total_price');

        $totalOrders = Order::where('status', 'completed')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->count();

        // LOGIC THÔNG MINH: Lọc theo Ngày hoặc theo Tháng
        // Nếu <= 31 ngày thì format là Năm-Tháng-Ngày, nếu > 31 thì format là Năm-Tháng
        $format = $diffInDays <= 31 ? '%Y-%m-%d' : '%Y-%m';

        $chartData = Order::select(
            DB::raw("DATE_FORMAT(updated_at, '$format') as date_label"),
            DB::raw('SUM(total_price) as total_revenue')
        )
            ->where('status', 'completed')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->groupBy('date_label')
            ->orderBy('date_label', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue,
                'total_orders'  => $totalOrders,
                'chart_data'    => $chartData
            ]
        ]);
    }

    // --- BÁO CÁO NHẬP XUẤT KHO ---
    public function getInventoryReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = \Carbon\Carbon::parse($request->start_date)->startOfDay();
        $endDate = \Carbon\Carbon::parse($request->end_date)->endOfDay();
        $diffInDays = $startDate->diffInDays($endDate);

        $format = $diffInDays <= 31 ? '%Y-%m-%d' : '%Y-%m';

        // SỬA Ở ĐÂY: Kết nối (Join) với bảng ingredients để tính ra số tiền
        $data = IngredientTransaction::select(
            DB::raw("DATE_FORMAT(created_at, '$format') as label"),
            'type',
            DB::raw('SUM(total_price) as total') // Bùm! Chỉ cần SUM(total_price) là xong
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('label', 'type')
            ->orderBy('label', 'asc')
            ->get();

        // --- XỬ LÝ FORMAT DỮ LIỆU ---
        $labels = [];
        $imports = [];
        $exports = [];

        $groupedData = $data->groupBy('label');

        foreach ($groupedData as $label => $transactions) {
            if ($diffInDays > 31) {
                $labels[] = "Tháng " . substr($label, 5, 2) . "/" . substr($label, 0, 4);
            } else {
                $labels[] = date('d/m', strtotime($label));
            }

            $importTotal = 0;
            $exportTotal = 0;

            foreach ($transactions as $trans) {
                if ($trans->type === 'import') {
                    $importTotal += $trans->total;
                } elseif ($trans->type === 'export') {
                    $exportTotal += $trans->total;
                }
            }

            $imports[] = $importTotal;
            $exports[] = $exportTotal;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'imports' => $imports,
                'exports' => $exports
            ]
        ]);
    }
}
