<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;


class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
}
