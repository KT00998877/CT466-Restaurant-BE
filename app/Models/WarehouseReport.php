<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseReport extends Model
{
    use HasFactory;

    protected $table = 'warehouse_reports';

    protected $fillable = [
        'report_date',
        'status',
        'created_by',
    ];

    protected $casts = [
        'report_date' => 'date',
    ];

    // Quan hệ: 1 báo cáo có nhiều items
    public function items()
    {
        return $this->hasMany(WarehouseReportItem::class);
    }

    // Quan hệ: Báo cáo được tạo bởi user nào
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
