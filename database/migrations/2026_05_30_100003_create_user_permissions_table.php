<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
            $table->foreignId('granted_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('expired_at')->nullable(); // null = vĩnh viễn
            $table->text('reason')->nullable(); // lý do gán quyền
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
            $table->index('user_id');
            $table->index('permission_id');
            $table->index('expired_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
};
