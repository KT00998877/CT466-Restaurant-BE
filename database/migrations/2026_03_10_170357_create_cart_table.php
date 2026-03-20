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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('menu_item_id')
                ->constrained('menu_items')
                ->onDelete('cascade');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedBigInteger('price'); // Giá tại thời điểm thêm vào giỏ
            $table->timestamps();
            // Mỗi user chỉ có 1 dòng cho mỗi món
            $table->unique(['user_id', 'menu_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
