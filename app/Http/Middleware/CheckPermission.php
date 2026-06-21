<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        // Nếu user chưa đăng nhập
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // Kiểm tra permission
        if ($user->role === 'admin') {
            return $next($request);
        }

        if (!$user->hasAnyPermission($permissions)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Bạn không có quyền này'
            ], 403);
        }

        return $next($request);
    }
}
