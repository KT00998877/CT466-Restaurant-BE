<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail; // <-- THÊM DÒNG NÀY
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable implements MustVerifyEmail 
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_id',
        'phone',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['avatar_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * Trả về URL đầy đủ của avatar.
     * Nếu chưa có avatar, trả về null.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        // Nếu đã là URL đầy đủ (ví dụ dùng S3) thì trả về thẳng
        if (str_starts_with($this->avatar, 'http')) {
            return $this->avatar;
        }

        // Trả về URL public (storage:link phải được chạy trước)
        return asset('storage/' . $this->avatar);
    }

    // Tính hạng thành viên dựa trên điểm tích lũy
    public function getMemberTierAttribute(): string
    {
        return match (true) {
            $this->points >= 1000 => 'Vàng',
            $this->points >= 500  => 'Bạc',
            default               => 'Đồng',
        };
    }

    // Quan hệ: một user có nhiều giao dịch điểm
    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }

    // ────── PERMISSIONS ──────
    // Quan hệ: User có nhiều quyền riêng lẻ (assigned by admin)
    public function userPermissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot('granted_by', 'granted_at', 'expired_at', 'reason')
            ->withTimestamps();
    }

    public function getRolePermissions()
    {
        return Permission::whereHas('roles', function ($query) {
            $query->where(function ($roleQuery) {
                if ($this->role_id) {
                    $roleQuery->where('roles.id', $this->role_id);
                }

                if ($this->role) {
                    $roleQuery->orWhere('roles.name', $this->role);
                }
            });
        })->get();
    }

    // Lấy tất cả permissions: từ role + quyền riêng (chưa hết hạn)
    public function getAllPermissions()
    {
        // Quyền từ role
        $rolePermissions = $this->getRolePermissions();

        // Quyền riêng lẻ (chưa hết hạn)
        $userPermissions = $this->userPermissions()
            ->where(function ($query) {
                $query->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            })
            ->get();

        return $rolePermissions->merge($userPermissions)->unique('id');
    }

    // Check user có quyền gì không
    public function hasPermission($permissionSlug)
    {
        return $this->getAllPermissions()->contains('slug', $permissionSlug);
    }

    // Check user có quyền nào trong danh sách
    public function hasAnyPermission(array $permissionSlugs)
    {
        $permissions = $this->getAllPermissions()->pluck('slug')->toArray();
        return count(array_intersect($permissions, $permissionSlugs)) > 0;
    }

    // Check user có tất cả quyền trong danh sách
    public function hasAllPermissions(array $permissionSlugs)
    {
        $permissions = $this->getAllPermissions()->pluck('slug')->toArray();
        return count(array_intersect($permissions, $permissionSlugs)) === count($permissionSlugs);
    }

    // Quan hệ: Role (nếu có role_id)
    public function roleModel()
    {
        return $this->belongsTo(Role::class);
    }
}
