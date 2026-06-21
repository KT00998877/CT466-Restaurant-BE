<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // ví dụ: "warehouse-report.save"
            $table->string('slug')->unique(); // ví dụ: warehouse_report_save
            $table->string('description')->nullable();
            $table->enum('type', ['menu', 'api'])->default('api'); // menu hoặc api
            $table->string('icon')->nullable();
            $table->string('route')->nullable(); // route hoặc api endpoint
            $table->timestamps();

            $table->index('slug');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
