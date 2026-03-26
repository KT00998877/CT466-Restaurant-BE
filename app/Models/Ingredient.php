<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
{
    use HasFactory;

    protected $table = 'ingredients';

    // Các trường được phép thêm/sửa hàng loạt (Mass Assignment)
    protected $fillable = [
        'name',
        'unit',
        'stock_quantity',
        'reorder_level',
    ];

    
    protected $casts = [
        'stock_quantity' => 'float',
        'reorder_level' => 'float',
    ];

    // Định nghĩa quan hệ: 1 Nguyên liệu có thể thuộc về nhiều Món ăn
    public function menuItems()
    {
        return $this->belongsToMany(MenuItem::class, 'ingredient_menu_item')
            ->using(IngredientMenuItem::class) // Sử dụng Pivot Model (nếu có)
            ->withPivot('quantity_required')   // Lấy thêm cột định lượng
            ->withTimestamps();
    }
}
