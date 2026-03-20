<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Ai là người đặt hàng
            $table->foreignId('table_id')->nullable()->constrained('table_lists')->nullOnDelete(); // Bàn ăn (nếu có)
            $table->decimal('total_price', 15, 2); // Tổng tiền hóa đơn
            $table->string('status')->default('pending'); // Trạng thái: pending, processing, completed, cancelled

            // Các thông tin giao hàng (có thể nullable nếu khách tự tới lấy hoặc ăn tại quán)
            $table->string('customer_name')->nullable();;
            $table->string('customer_phone')->nullable();;
            $table->string('customer_address')->nullable();
            $table->text('notes')->nullable(); // Ghi chú thêm

            $table->string('payment_method')->default('cod'); // cod, vnpay, momo...
            $table->string('payment_status')->default('unpaid'); // unpaid, paid

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
