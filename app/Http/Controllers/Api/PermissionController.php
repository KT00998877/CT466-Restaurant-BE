<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\User;
use App\Models\PermissionLog;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    // 1. Lấy danh sách tất cả permissions
    public function getAllPermissions()
    {
        $permissions = Permission::orderBy('type', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    // 2. Lấy permissions của 1 user (từ role + quyền riêng)
    public function getUserPermissions($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy user'], 404);
        }

        $rolePermissions = $user->getRolePermissions();

        $userPermissions = $user->userPermissions()
            ->where(function ($query) {
                $query->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'role_permissions' => $rolePermissions,
                'user_permissions' => $userPermissions,
                'all_permissions' => $user->getAllPermissions(),
            ]
        ]);
    }

    // 3. Gán permission cho user
    public function grantPermission(Request $request, $userId)
    {
        // Chỉ admin mới có quyền
        if ($request->user()->role !== 'admin' && !$request->user()->hasPermission('permissions.grant')) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền gán permission'], 403);
        }

        $validated = $request->validate([
            'permission_id' => 'required|exists:permissions,id',
            'reason' => 'nullable|string',
            'expired_at' => 'nullable|date|after:now',
        ]);

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy user'], 404);
        }

        try {
            DB::beginTransaction();

            $pivotData = [
                'granted_by' => $request->user()->id,
                'granted_at' => now(),
                'expired_at' => $validated['expired_at'] ?? null,
                'reason' => $validated['reason'] ?? null,
            ];

            if ($user->userPermissions()->where('permissions.id', $validated['permission_id'])->exists()) {
                $user->userPermissions()->updateExistingPivot($validated['permission_id'], $pivotData);
            } else {
                $user->userPermissions()->attach($validated['permission_id'], $pivotData);
            }

            // Log audit
            PermissionLog::create([
                'admin_id' => $request->user()->id,
                'user_id' => $userId,
                'permission_id' => $validated['permission_id'],
                'action' => 'grant',
                'reason' => $validated['reason'] ?? null,
                'metadata' => [
                    'expired_at' => $validated['expired_at'] ?? null,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Gán quyền thành công',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    // 4. Thu hồi permission từ user
    public function revokePermission(Request $request, $userId, $permissionId)
    {
        // Chỉ admin mới có quyền
        if ($request->user()->role !== 'admin' && !$request->user()->hasPermission('permissions.revoke')) {
            return response()->json(['success' => false, 'message' => 'Bạn không có quyền thu hồi permission'], 403);
        }

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy user'], 404);
        }

        $permission = Permission::find($permissionId);
        if (!$permission) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy permission'], 404);
        }

        try {
            DB::beginTransaction();

            // Thu hồi quyền
            $user->userPermissions()->detach($permissionId);

            // Log audit
            PermissionLog::create([
                'admin_id' => $request->user()->id,
                'user_id' => $userId,
                'permission_id' => $permissionId,
                'action' => 'revoke',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Thu hồi quyền thành công',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ], 500);
        }
    }

    // 5. Lấy audit log
    public function getAuditLog(Request $request)
    {
        $logs = PermissionLog::with(['admin:id,name', 'user:id,name', 'permission:id,name,slug'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    // 6. Lấy audit log của 1 user
    public function getUserAuditLog($userId, Request $request)
    {
        $logs = PermissionLog::where('user_id', $userId)
            ->with(['admin:id,name', 'permission:id,name,slug'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    // 7. Kiểm tra user có permission không (dùng cho frontend)
    public function checkPermission($userId, $permissionSlug)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json(['success' => false, 'has_permission' => false], 404);
        }

        $hasPermission = $user->hasPermission($permissionSlug);

        return response()->json([
            'success' => true,
            'has_permission' => $hasPermission,
            'user_id' => $userId,
            'permission_slug' => $permissionSlug,
        ]);
    }

    // 8. Lấy danh sách users và permissions của họ (cho admin dashboard)
    public function getUsersWithPermissions(Request $request)
    {
        $users = User::with(['roleModel:id,name', 'userPermissions'])
            ->get()
            ->map(function ($user) {
                $rolePermissions = $user->getRolePermissions();

                $userPermissions = $user->userPermissions()
                    ->where(function ($query) {
                        $query->whereNull('expired_at')
                            ->orWhere('expired_at', '>', now());
                    })
                    ->get();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'role_name' => $user->roleModel?->name,
                    'role_permissions' => $rolePermissions,
                    'role_permission_slugs' => $rolePermissions->pluck('slug'),
                    'user_specific_permissions' => $userPermissions,
                    'user_specific_permission_slugs' => $userPermissions->pluck('slug'),
                    'permissions' => $user->getAllPermissions()->pluck('slug'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}
