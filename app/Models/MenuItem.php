<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

   
    protected $fillable = [
        'name',
        'category_id',
        'description',
        'price',
        'img_url',
        'is_combo',
        'status',
    ];

    const STATUS_INACTIVE = 0;      // Ngừng bán
    const STATUS_ACTIVE = 1;        // Đang bán
    const STATUS_OUT_OF_STOCK = 2;  // Tạm hết hàng

    protected $casts = [
        'is_combo' => 'boolean', 
        'price' => 'integer',  
        'status' => 'integer',
    ];

    /**
     * Mối quan hệ: 1 Món ăn thuộc về 1 Danh mục
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'Đang kinh doanh',
            self::STATUS_INACTIVE => 'Ngừng kinh doanh',
            self::STATUS_OUT_OF_STOCK => 'Tạm hết nguyên liệu',
            default => 'Không xác định',
        };
    }
}
