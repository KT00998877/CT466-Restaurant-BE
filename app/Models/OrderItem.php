<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Chi tiết hóa đơn thuộc về một hóa đơn
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Chi tiết hóa đơn liên kết với một món ăn gốc
    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}