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
        Schema::create('warehouse_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_report_id')->constrained('warehouse_reports')->onDelete('cascade');
            $table->foreignId('ingredient_id')->constrained('ingredients')->onDelete('cascade');
            $table->float('opening_stock'); // Tồn đầu
            $table->float('import_quantity')->default(0); // Nhập
            $table->float('export_quantity')->default(0); // Xuất
            $table->float('closing_stock'); // Tồn cuối (tính = opening + import - export)
            $table->text('note')->nullable(); // Ghi chú
            $table->timestamps();

            $table->unique(['warehouse_report_id', 'ingredient_id']);
            $table->index('warehouse_report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_report_items');
    }
};
