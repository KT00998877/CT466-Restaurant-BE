<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
class ContactController extends Controller
{
    // API dành cho khách hàng gửi liên hệ
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Trang liên hệ'
        ]);
    }
    // 1. Khách hàng gửi liên hệ
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'phone'   => 'nullable|string|max:20',
        ], [
            'name.required'    => 'Vui lòng nhập họ tên.',
            'email.required'   => 'Vui lòng nhập email.',
            'email.email'      => 'Email không hợp lệ.',
            'subject.required' => 'Vui lòng nhập tiêu đề.',
            'message.required' => 'Vui lòng nhập nội dung.',
        ]);

        $contact = Contact::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Gửi liên hệ thành công! Chúng tôi sẽ phản hồi sớm nhất.',
            'data'    => $contact,
        ], 201);
    }

    // ==========================================
    // CÁC API DÀNH CHO ADMIN QUẢN LÝ
    // ==========================================

    // 2. Lấy danh sách liên hệ
    public function indexAdmin(Request $request)
    {
        // Sắp xếp tin nhắn mới nhất lên đầu, hỗ trợ phân trang
        $contacts = Contact::orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $contacts
        ]);
    }

    // 3. Cập nhật trạng thái (Đã đọc / Đã phản hồi)
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:new,read,replied'
        ]);

        $contact = Contact::findOrFail($id);
        $contact->status = $request->status;
        $contact->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công'
        ]);
    }

    // 4. Xóa tin nhắn liên hệ
    public function destroy($id)
    {
        $contact = Contact::findOrFail($id);
        $contact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa liên hệ'
        ]);
    }
}
