<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('ingredient_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable(); // Lưu ID của quản lý/người thao tác
            $table->enum('type', ['import', 'export']); // Trạng thái: Nhập hoặc Xuất
            $table->decimal('quantity', 8, 2); // Số lượng thay đổi
            $table->decimal('stock_after', 8, 2); // Tồn kho sau khi thay đổi (Rất quan trọng để đối soát)
            $table->string('note')->nullable(); // Lý do (Vd: Nhập hàng NCC A, Hủy do hư hỏng...)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_transactions');
    }
};
