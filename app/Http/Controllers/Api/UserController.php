<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // Lấy danh sách toàn bộ người dùng (Khách hàng & Admin)
    public function index()
    {
        $users = User::orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    // Cập nhật thông tin & phân quyền
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy người dùng'], 404);
        }

        // Validate dữ liệu gửi lên (Bỏ qua email hiện tại của user này để không bị báo lỗi "Email đã tồn tại")
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|in:admin,customer',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($request->only(['name', 'email', 'role', 'phone']));

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật thông tin thành công!',
            'data' => $user
        ]);
    }

    // Xóa người dùng
    public function destroy(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy người dùng'], 404);
        }

        // Ngăn chặn Admin tự xóa chính tài khoản của mình đang đăng nhập
        if ($request->user() && $request->user()->id == $id) {
            return response()->json(['success' => false, 'message' => 'Bạn không thể tự xóa tài khoản của chính mình!'], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa người dùng thành công!'
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,customer,cashier,waiter', 
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'role' => $request->role,
            'password' => bcrypt($request->password), 
        ]);

        return response()->json(['success' => true, 'message' => 'Đã thêm người dùng', 'data' => $user]);
    }
}
