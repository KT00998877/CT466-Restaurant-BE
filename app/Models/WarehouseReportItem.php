<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseReportItem extends Model
{
    use HasFactory;

    protected $table = 'warehouse_report_items';

    protected $fillable = [
        'warehouse_report_id',
        'ingredient_id',
        'opening_stock',
        'import_quantity',
        'export_quantity',
        'closing_stock',
        'note',
    ];

    protected $casts = [
        'opening_stock' => 'float',
        'import_quantity' => 'float',
        'export_quantity' => 'float',
        'closing_stock' => 'float',
    ];

    // Quan hệ: Mỗi item thuộc về 1 báo cáo
    public function report()
    {
        return $this->belongsTo(WarehouseReport::class, 'warehouse_report_id');
    }

    // Quan hệ: Mỗi item là 1 nguyên liệu
    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class, 'ingredient_id');
    }
}
