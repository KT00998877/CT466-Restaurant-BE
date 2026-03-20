<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = []; // Cho phép mass assignment tất cả các cột

    // Một hóa đơn thuộc về một User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Một hóa đơn có nhiều chi tiết hóa đơn (món ăn)
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function table()
    {
        return $this->belongsTo(TableList::class, 'table_id');
    }
}