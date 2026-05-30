<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ingredient_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('ingredient_transactions', 'unit_price')) {
                $table->decimal('unit_price', 15, 2)->default(0)->after('quantity')->comment('Đơn giá tại thời điểm giao dịch');
            }
            if (!Schema::hasColumn('ingredient_transactions', 'total_price')) {
                $table->decimal('total_price', 15, 2)->default(0)->after('unit_price')->comment('Tổng tiền = quantity * unit_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredient_transactions', function (Blueprint $table) {
            //
        });
    }
};
