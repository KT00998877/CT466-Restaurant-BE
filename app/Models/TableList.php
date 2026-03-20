<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableList extends Model
{
    use HasFactory;

    // BẮT BUỘC: Khai báo rõ tên bảng vì nó không theo quy tắc số nhiều mặc định
    protected $table = 'table_lists';

    // Các cột được phép lưu dữ liệu
    protected $fillable = [
        'name',
        'capacity',
        'status',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'table_id');
    }
    public function activeOrder()
    {
        return $this->hasOne(Order::class, 'table_id')->where('status', 'serving');
    }
}
