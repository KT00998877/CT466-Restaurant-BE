<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $table = 'permissions';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'icon',
        'route',
    ];

    // Quan hệ: Permission có nhiều roles
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    // Quan hệ: Permission có nhiều users (quyền riêng)
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot('granted_by', 'granted_at', 'expired_at', 'reason')
            ->withTimestamps();
    }

    // Lấy tất cả users có quyền này (từ role hoặc quyền riêng)
    public function getAllUsers()
    {
        $fromRoles = User::whereHas('roleModel.permissions', function ($query) {
            $query->where('permission_id', $this->id);
        })->get();

        $fromUserPermissions = $this->users()
            ->where(function ($query) {
                $query->whereNull('expired_at')
                    ->orWhere('expired_at', '>', now());
            })
            ->get();

        return $fromRoles->merge($fromUserPermissions)->unique('id');
    }
}
