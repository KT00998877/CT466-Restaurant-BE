<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use  App\Models\PointTransaction;

class UserController extends Controller
{
    // =========================================================
    // ADMIN: Danh sách, thêm, sửa, xóa người dùng
    // =========================================================

    public function index()
    {
        $users = User::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
    // 1. Thêm người dùng mới
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
            'role'     => 'required|in:admin,user,cashier,waiter',
            'phone'    => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'role'     => $request->role,
            'password' => bcrypt($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm người dùng',
            'data'    => $user
        ]);
    }
    // 2. Cập nhật thông tin người dùng (Không cập nhật mật khẩu ở đây, có API riêng để đổi mật khẩu)
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy người dùng'], 404);
        }

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role'  => 'required|in:admin,user,cashier,waiter',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($request->only(['name', 'email', 'role', 'phone']));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công!',
            'data'    => $user
        ]);
    }

    // 3. Xóa người dùng
    
    public function destroy(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy người dùng'], 404);
        }

        if ($request->user() && $request->user()->id == $id) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không thể tự xóa tài khoản của chính mình!'
            ], 403);
        }

        // Xóa avatar cũ nếu có
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa người dùng thành công!'
        ]);
    }

    // =========================================================
    // PROFILE: Người dùng tự quản lý tài khoản cá nhân
    // =========================================================

    /**
     * Lấy thông tin profile của user đang đăng nhập.
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => $request->user()
        ]);
    }

    /**
     * Cập nhật tên và số điện thoại.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($request->only(['name', 'phone']));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin cá nhân thành công!',
            'data'    => $user
        ]);
    }

    /**
     * Upload ảnh đại diện (avatar).
     *
     * POST /api/profile/avatar
     * Body: multipart/form-data  →  avatar: <file>
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048', // tối đa 2MB
        ]);

        $user = $request->user();

        // Xóa ảnh cũ nếu đã có (tránh tích lũy file thừa)
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Lưu file mới vào storage/app/public/avatars/{filename}
        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return response()->json([
            'success'    => true,
            'message'    => 'Cập nhật ảnh đại diện thành công!',
            'avatar_url' => $user->avatar_url, // trả về URL đầy đủ
        ]);
    }

    /**
     * Đổi mật khẩu.
     *
     * PUT /api/profile/password
     * Body JSON: { current_password, new_password, new_password_confirmation }
     */
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password'      => 'required|string',
            'new_password'          => 'required|string|min:6|confirmed', // yêu cầu new_password_confirmation
        ]);

        // Kiểm tra mật khẩu hiện tại có đúng không
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng.',
            ], 422);
        }

        // Ngăn đặt lại mật khẩu giống mật khẩu cũ
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu mới không được trùng với mật khẩu hiện tại.',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Thu hồi tất cả token cũ → bắt user đăng nhập lại (tuỳ chọn)
        // $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đổi mật khẩu thành công!',
        ]);
    }

    // Lay thong tin khach hang theo so dien thoai (dung cho Waiter khi checkout)
    public function searchByPhone(Request $request)
    {
        $phone = $request->query('phone');

        if (!$phone) {
            return response()->json(['success' => false, 'message' => 'Vui lòng cung cấp số điện thoại']);
        }

        // Tìm khách hàng theo sđt và role
        $customer = User::where('phone', $phone)->where('role', 'user')->first();

        if ($customer) {
            return response()->json([
                'success' => true,
                'customer' => [ 
                    'id' => $customer->id, 
                    'name' => $customer->name,
                    'phone' => $customer->phone,
                    'points' => $customer->points
                ]
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Không tìm thấy khách hàng']);
    }

    //Lấy lịch sử tích điểm của user đang đăng nhập
    public function pointHistory(Request $request)
    {
        // Lấy danh sách lịch sử của user đang đăng nhập, sắp xếp mới nhất lên đầu
        $history = PointTransaction::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }
}
