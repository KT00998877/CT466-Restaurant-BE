<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
class ContactController extends Controller
{
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Trang liên hệ'
        ]);
    }

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
}
