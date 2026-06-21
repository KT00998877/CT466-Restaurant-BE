<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegisterOtpMail;
use App\Mail\ForgotPasswordOtpMail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Đăng ký tài khoản mới
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'regex:/^(0[3|5|7|8|9])+([0-9]{8})$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'name.required'      => 'Vui lòng nhập họ tên.',
            'email.required'     => 'Vui lòng nhập email.',
            'email.email'        => 'Email không hợp lệ.',
            'email.unique'       => 'Email này đã được sử dụng.',
            'password.required'  => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min'       => 'Mật khẩu tối thiểu 8 ký tự.',
        ]);

        // ĐÃ XÓA ĐOẠN USER::CREATE Ở ĐÂY!!!
        // Thay vì lưu Database, chúng ta đi thẳng vào bước tạo OTP và lưu Cache.

        // Tạo mã OTP ngẫu nhiên 6 số
        $otp = rand(100000, 999999);

        // Chuẩn bị dữ liệu lưu tạm
        $userData = [
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password), // Mã hóa pass luôn cho an toàn
            'role'     => 'user',
            'role_id'  => Role::where('name', 'user')->value('id'),
            'otp'      => $otp
        ];

        // Lưu vào Cache với key là email, tồn tại trong 5 phút
        Cache::put('register_otp_' . $request->email, $userData, now()->addMinutes(5));

        // Gửi email
        Mail::to($request->email)->send(new RegisterOtpMail($otp));

        return response()->json([
            'message' => 'Mã xác thực đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.',
            'email'   => $request->email // Trả về email để frontend dùng gọi hàm verify
        ], 200);
    }
    /**
     * BƯỚC 2: Xác nhận OTP và tạo tài khoản vào DB
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            // Bổ sung unique:users ở đây để chặn lỗi Database nếu spam request
            'email' => ['required', 'email', 'unique:users'],
            'otp'   => ['required', 'numeric'],
        ], [
            'email.unique' => 'Email này đã được xác thực và tạo tài khoản trước đó.'
        ]);

        $cacheKey = 'register_otp_' . $request->email;
        $cachedData = Cache::get($cacheKey);

        // Kiểm tra xem cache còn tồn tại không
        if (!$cachedData) {
            return response()->json(['message' => 'Mã xác thực đã hết hạn hoặc email không đúng.'], 400);
        }

        // Kiểm tra mã OTP
        if ((string)$cachedData['otp'] !== (string)$request->otp) {
            return response()->json(['message' => 'Mã xác thực không chính xác.'], 400);
        }

        // BÂY GIỜ MỚI THỰC SỰ LƯU VÀO DATABASE
        $user = User::create([
            'name'     => $cachedData['name'],
            'email'    => $cachedData['email'],
            'phone'    => $cachedData['phone'],
            'password' => $cachedData['password'],
            'role'     => $cachedData['role'],
            'role_id'  => $cachedData['role_id'] ?? Role::where('name', $cachedData['role'])->value('id'),
        ]);

        // Đánh dấu đã xác thực email
        $user->markEmailAsVerified();

        // Xóa cache để OTP không được dùng lại
        Cache::forget($cacheKey);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng ký tài khoản thành công!',
            'user'    => $user,
            'token'   => $token,
        ], 201);
    }
    /**
     * Đăng nhập
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'Vui lòng nhập email.',
            'email.email'       => 'Email không hợp lệ.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không đúng.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        // Xóa token cũ (optional - single session)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công!',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role'  => $user->role,
                'role_id' => $user->role_id,
                'permissions' => $user->getAllPermissions()->pluck('slug'),
            ],
            'token'      => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Đăng xuất thành công!',
        ]);
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => [
                'id'    => $request->user()->id,
                'name'  => $request->user()->name,
                'email' => $request->user()->email,
                'phone' => $request->user()->phone,
                'role'  => $request->user()->role,
                'role_id' => $request->user()->role_id,
                'permissions' => $request->user()->getAllPermissions()->pluck('slug'),
            ],
        ]);
    }

    /**
     * Xử lý xác thực email khi Vue gọi API lên
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $user = User::find($request->id);

        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy tài khoản.'], 404);
        }

        // Kiểm tra mã hash có khớp với email của user hay không
        if (!hash_equals((string) $request->hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Đường dẫn xác thực không hợp lệ hoặc đã bị thay đổi.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Tài khoản của bạn đã được xác thực từ trước.'], 200);
        }

        // Đánh dấu email đã được xác thực
        $user->markEmailAsVerified();

        return response()->json(['message' => 'Xác thực email thành công! Bạn có thể đăng nhập ngay bây giờ.'], 200);
    }

    /**
     * Gửi lại email xác thực (Nếu user yêu cầu)
     */
    public function resendVerificationEmail(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email của bạn đã được xác thực trước đó.'], 200);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Link xác thực đã được gửi lại. Vui lòng kiểm tra hộp thư của bạn.']);
    }

    /**
     * Gửi OTP Khôi phục mật khẩu
     */
    public function sendResetOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'Vui lòng nhập email.',
            'email.email'    => 'Email không hợp lệ.',
            'email.exists'   => 'Email này chưa được đăng ký trong hệ thống.',
        ]);

        $otp = rand(100000, 999999);

        // Lưu OTP vào cache 5 phút
        Cache::put('reset_otp_' . $request->email, $otp, now()->addMinutes(5));

        // Gửi mail
        Mail::to($request->email)->send(new ForgotPasswordOtpMail($otp));

        return response()->json([
            'message' => 'Mã OTP khôi phục mật khẩu đã được gửi đến email của bạn.'
        ], 200);
    }

    /**
     * BƯỚC 2: Xác nhận OTP (Chỉ kiểm tra OTP, chưa đổi mật khẩu)
     */
    public function verifyResetOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp'   => ['required', 'numeric'],
        ]);

        $cachedOtp = Cache::get('reset_otp_' . $request->email);

        if (!$cachedOtp || (string)$cachedOtp !== (string)$request->otp) {
            return response()->json(['message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.'], 400);
        }

        // Nếu OTP đúng, tạo ra một Token tạm thời để cho phép đổi mật khẩu (Sống 15 phút)
        $resetToken = Str::random(60);
        Cache::put('reset_token_' . $request->email, $resetToken, now()->addMinutes(15));

        // Xóa OTP cũ cho an toàn (Tránh dùng lại)
        Cache::forget('reset_otp_' . $request->email);

        return response()->json([
            'message' => 'Xác thực thành công. Vui lòng tạo mật khẩu mới.',
            'reset_token' => $resetToken
        ], 200);
    }

    /**
     * BƯỚC 3: Cập nhật mật khẩu mới (Cần có reset_token)
     */
    public function updateNewPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => ['required', 'email', 'exists:users,email'],
            'token'    => ['required', 'string'],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)],
        ], [
            'password.required'  => 'Vui lòng nhập mật khẩu mới.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'password.min'       => 'Mật khẩu tối thiểu 8 ký tự.',
        ]);

        $cachedToken = Cache::get('reset_token_' . $request->email);

        // Kiểm tra xem token gửi lên có khớp với cache không
        if (!$cachedToken || $cachedToken !== $request->token) {
            return response()->json(['message' => 'Phiên làm việc không hợp lệ hoặc đã hết hạn. Vui lòng thao tác lại.'], 403);
        }

        // Tìm user và đổi mật khẩu
        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        // Xóa token đi
        Cache::forget('reset_token_' . $request->email);

        return response()->json(['message' => 'Đổi mật khẩu thành công!'], 200);
    }
}
