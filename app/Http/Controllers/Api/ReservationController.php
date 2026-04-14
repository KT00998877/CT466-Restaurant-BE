<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\TableList;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * Lấy toàn bộ danh sách bàn
     * Nếu truyền ?reserved_at=... thì đánh dấu bàn đã bị đặt trong khung giờ đó
     */
    public function getTables(Request $request): JsonResponse
    {
        $reservedAt = $request->input('reserved_at');

        $tables = TableList::all()->map(function ($table) use ($reservedAt) {

            $status = $table->status; // 'available' | 'unavailable'

            // Chỉ check conflict khi frontend truyền reserved_at
            if ($reservedAt && $status === 'available') {
                $isBooked = Reservation::where('table_id', $table->id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->whereBetween('reserved_at', [
                        Carbon::parse($reservedAt)->subHours(3),
                        Carbon::parse($reservedAt)->addHours(3),
                    ])
                    ->exists();

                if ($isBooked) $status = 'booked';
            }

            return [
                'id'       => $table->id,
                'name'     => $table->name,
                'capacity' => $table->capacity,
                'status'   => $status,
            ];
        });

        return response()->json(['tables' => $tables]);
    }

    /**
     * Đặt bàn
     * Flow: chọn bàn → điền thông tin (ngày giờ, khách...) → gửi
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'table_id'       => ['required', 'exists:table_lists,id'],
            'customer_name'  => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:20'],
            'guests'         => ['required', 'integer', 'min:1'],
            'reserved_at'    => ['required', 'date', 'after:now'],
            'note'           => ['nullable', 'string', 'max:500'],
        ], [
            'table_id.required'       => 'Vui lòng chọn bàn.',
            'table_id.exists'         => 'Bàn không tồn tại.',
            'customer_name.required'  => 'Vui lòng nhập họ tên.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại.',
            'guests.required'         => 'Vui lòng nhập số khách.',
            'guests.min'              => 'Số khách tối thiểu là 1.',
            'reserved_at.required'    => 'Vui lòng chọn ngày giờ đặt bàn.',
            'reserved_at.after'       => 'Thời gian đặt bàn phải sau thời điểm hiện tại.',
        ]);

        $table = TableList::findOrFail($data['table_id']);

        // 1. Bàn có khả dụng không
        if ($table->status === 'unavailable') {
            return response()->json([
                'message' => 'Bàn này hiện không khả dụng.',
            ], 422);
        }

        // 2. Kiểm tra trùng lịch ±3 giờ
        $conflict = Reservation::where('table_id', $data['table_id'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('reserved_at', [
                Carbon::parse($data['reserved_at'])->subHours(3),
                Carbon::parse($data['reserved_at'])->addHours(3),
            ])
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'Bàn này đã được đặt trong khung giờ bạn chọn. Vui lòng chọn giờ khác.',
            ], 422);
        }

        // 3. Kiểm tra sức chứa
        if ($data['guests'] > $table->capacity) {
            return response()->json([
                'message' => "Bàn {$table->name} chỉ chứa tối đa {$table->capacity} khách.",
            ], 422);
        }

        $reservation = Reservation::create([
            ...$data,
            'user_id' => $request->user()?->id,
            'status'  => 'pending',
        ]);

        return response()->json([
            'message'     => 'Đặt bàn thành công! Chúng tôi sẽ xác nhận sớm.',
            'reservation' => $reservation->load('table'),
        ], 201);
    }

    /**
     * Lịch sử đặt bàn của user hiện tại
     */
    public function myReservations(Request $request): JsonResponse
    {
        $reservations = Reservation::with('table')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('reserved_at')
            ->get();

        return response()->json(['reservations' => $reservations]);
    }

    /**
     * Hủy đặt bàn
     */
    public function cancel(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Không có quyền hủy đặt bàn này.',
            ], 403);
        }

        if (!in_array($reservation->status, ['pending', 'confirmed'])) {
            return response()->json([
                'message' => 'Không thể hủy đặt bàn ở trạng thái này.',
            ], 422);
        }

        $reservation->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Hủy đặt bàn thành công.']);
    }

    /**
     * [ADMIN] Danh sách toàn bộ đặt bàn
     */
    public function adminIndex(): JsonResponse
    {
        $reservations = Reservation::with(['table', 'user'])
            ->orderByDesc('reserved_at')
            ->get();

        return response()->json(['reservations' => $reservations]);
    }

    /**
     * [ADMIN] Cập nhật trạng thái
     */
    public function updateStatus(Request $request, Reservation $reservation): JsonResponse
    {
        // 1. Thêm trạng thái 'seated' vào validate
        $request->validate([
            'status' => ['required', 'in:pending,confirmed,seated,completed,cancelled'],
        ]);

        $oldStatus = $reservation->status;
        $newStatus = $request->status;

        // 2. Cập nhật trạng thái đặt bàn
        $reservation->update(['status' => $newStatus]);

        // 3. ĐỒNG BỘ TRẠNG THÁI BÀN THỰC TẾ (TableList)
        $table = $reservation->table()->first();

        if ($table) {
            // Khách đến nhận bàn -> Chuyển bàn thành ĐANG SỬ DỤNG
            if ($newStatus === 'seated') {
                $table->update(['status' => 'occupied']); // Phục vụ sẽ không mở bàn này được nữa
            }
            // Khách ăn xong (completed) hoặc Hủy bàn (cancelled) -> Trả lại BÀN TRỐNG
            elseif (in_array($newStatus, ['completed', 'cancelled'])) {
                // Chỉ trả về available nếu bàn đang ở trạng thái in_use hoặc unavailable do chính cái reservation này gây ra
                // (Thực tế phức tạp hơn có thể set thành 'cleaning' để chờ dọc dẹp xong mới available)
                $table->update(['status' => 'available']);
            }
        }

        return response()->json([
            'message'     => 'Cập nhật trạng thái thành công.',
            'reservation' => $reservation->load('table'),
        ]);
    }


    public function checkAvailability(Request $request)
    {
        $tableId = $request->table_id;
        $reservedAt = Carbon::parse($request->reserved_at);

        // Thiết lập khoảng cách an toàn là 3 tiếng (180 phút)
        $bufferMinutes = 180;

        // Tìm xem có đơn đặt bàn nào cho bàn này 
        // mà thời gian nằm trong khoảng: [Thời gian chọn - 3h] đến [Thời gian chọn + 3h]
        $isBooked = Reservation::where('table_id', $tableId)
            ->whereIn('status', ['pending', 'confirmed']) // Chỉ check các đơn chưa bị hủy
            ->whereBetween('reserved_at', [
                $reservedAt->copy()->subMinutes($bufferMinutes),
                $reservedAt->copy()->addMinutes($bufferMinutes)
            ])
            ->exists();

        return response()->json([
            'available' => !$isBooked
        ]);
    }
}
