<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\VerifyEmail; 
use Illuminate\Support\Carbon;                 
use Illuminate\Support\Facades\Config;        
use Illuminate\Support\Facades\URL;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // <-- THÊM ĐOẠN CODE NÀY VÀO HÀM BOOT -->
        VerifyEmail::createUrlUsing(function ($notifiable) {
            // Lấy URL của Vue từ file .env (Mặc định là localhost:5173 nếu không có)
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

            // Tạo link xác thực tạm thời có chữ ký bảo mật của Laravel
            $verifyUrl = URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            // Tách lấy phần query parameters (?id=...&hash=...)
            $query = parse_url($verifyUrl, PHP_URL_QUERY);

            // Trả về link trỏ sang Frontend Vue kèm theo query params
            return $frontendUrl . '/verify-email?' . $query;
        });
    }
}
