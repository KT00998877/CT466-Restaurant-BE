<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('table_id')->constrained('table_lists')->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->integer('guests');
            $table->dateTime('reserved_at');         // ngày giờ đặt bàn
            $table->string('note')->nullable();
            $table->string('status')->default('pending'); // pending | confirmed | cancelled | completed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
