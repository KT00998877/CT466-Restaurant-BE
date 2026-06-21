<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionLog extends Model
{
    use HasFactory;

    protected $table = 'permission_logs';

    protected $fillable = [
        'admin_id',
        'user_id',
        'permission_id',
        'action',
        'reason',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Quan hệ: Log thuộc về admin nào thực hiện
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Quan hệ: Log liên quan đến user nào
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Quan hệ: Permission được gán/thu hồi
    public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
