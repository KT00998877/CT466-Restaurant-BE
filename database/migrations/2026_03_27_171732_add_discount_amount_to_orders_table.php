<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Thêm cột discount_amount sau cột total_price
            // default(0) để các hóa đơn cũ không bị lỗi null
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total_price');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            // Xóa cột nếu rollback
            $table->dropColumn('discount_amount');
        });
    }
};
