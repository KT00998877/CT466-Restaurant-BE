<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Order extends Model
{
    use HasFactory;

    protected $guarded = []; // Cho phép mass assignment tất cả các cột

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Sinh mã đơn tự động: ORD + timestamp + random 4 ký tự
            if (!$model->order_code) {
                $model->order_code = 'ORD' . date('YmdHis') . strtoupper(substr(uniqid(), -4));
            }
        });
    }

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

    // Một hóa đơn có thể sinh ra nhiều giao dịch điểm
    public function pointTransactions()
    {
        return $this->hasMany(PointTransaction::class);
    }
}
