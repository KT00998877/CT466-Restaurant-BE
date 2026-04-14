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
        Schema::table('menu_items', function (Blueprint $table) {
            // Thêm 2 cột boolean, mặc định là false (0), đặt ngay sau cột 'status' cho gọn gàng
            $table->boolean('is_featured')->default(false)->after('status')->comment('1: Món đặc sắc, 0: Bình thường');
            $table->boolean('is_daily_special')->default(false)->after('is_featured')->comment('1: Món ngon mỗi ngày, 0: Bình thường');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Xóa 2 cột này nếu rollback migration
            $table->dropColumn(['is_featured', 'is_daily_special']);
        });
    }
};
