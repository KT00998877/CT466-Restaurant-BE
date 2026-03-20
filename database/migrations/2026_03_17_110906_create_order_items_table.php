<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade'); // Thuộc hóa đơn nào
            $table->foreignId('menu_item_id')->constrained()->onDelete('cascade'); // Món ăn nào

            // Lưu lại thông tin món ăn tại thời điểm mua (tránh trường hợp sau này món bị đổi giá/tên)
            $table->string('item_name');
            $table->decimal('price', 15, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 15, 2);

            // --- PHẦN DÀNH CHO BẾP ---
            $table->string('note')->nullable(); // Ghi chú (vd: ít cay, không hành)

            // Trạng thái cho bếp: pending (đang chờ nấu), completed (nấu xong)
            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_items');
    }
};
