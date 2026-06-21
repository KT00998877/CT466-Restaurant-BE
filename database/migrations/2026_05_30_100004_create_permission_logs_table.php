<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade'); // admin thực hiện
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // user bị gán/thu hồi
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->enum('action', ['grant', 'revoke']); // gán hoặc thu hồi
            $table->text('reason')->nullable(); // lý do
            $table->json('metadata')->nullable(); // thông tin thêm (expiry, etc)
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_logs');
    }
};
