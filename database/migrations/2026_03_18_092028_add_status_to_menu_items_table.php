<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            // Dùng tinyInteger, mặc định là 1 (Đang bán). Thêm comment để sau này đọc DB dễ hiểu.
            $table->tinyInteger('status')
                ->default(1)
                ->comment('1: Đang bán, 0: Ngừng bán, 2: Tạm hết hàng')
                ->after('is_combo');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
