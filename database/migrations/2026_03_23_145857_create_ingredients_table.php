<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Tên nguyên liệu
            $table->string('unit'); // Đơn vị tính (kg, gram, lít, chai, quả,...)
            $table->decimal('stock_quantity', 10, 2)->default(0); // Tồn kho hiện tại
            $table->decimal('reorder_level', 10, 2)->default(0); // Mức cảnh báo cần nhập thêm hàng
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
