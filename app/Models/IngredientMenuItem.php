<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class IngredientMenuItem extends Pivot
{
    protected $table = 'ingredient_menu_item';

    // Khai báo các cột có trong bảng trung gian
    protected $fillable = [
        'menu_item_id',
        'ingredient_id',
        'quantity_required',
    ];

    // Ép kiểu dữ liệu cho cột quantity_required để đảm bảo tính toán chính xác
    protected $casts = [
        'quantity_required' => 'decimal:3',
    ];
}
