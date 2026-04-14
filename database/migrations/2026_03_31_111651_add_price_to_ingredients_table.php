<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            // Thêm cột price (kiểu số nguyên), mặc định là 0, đặt ngay sau cột unit
            $table->integer('price')->default(0)->after('unit')->comment('Giá tham khảo trên 1 đơn vị (VNĐ)');
        });
    }

    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
