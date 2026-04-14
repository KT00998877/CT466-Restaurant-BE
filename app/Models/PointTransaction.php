<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'points',
        'type',   // 'earn' (cộng điểm), 'deduct' (trừ điểm), 'redeem' (đổi điểm)
        'note',
    ];

    // (Tùy chọn) Liên kết ngược lại với User và Order
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
