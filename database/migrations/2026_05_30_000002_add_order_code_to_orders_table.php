<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'order_code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('order_code')->nullable()->after('id');
            });
        }

        // Populate mã đơn cho các đơn hàng hiện tại (cả NULL lẫn '')
        $orders = DB::table('orders')->where(function($q) {
            $q->whereNull('order_code')->orWhere('order_code', '=', '');
        })->get();

        foreach ($orders as $order) {
            DB::table('orders')
                ->where('id', $order->id)
                ->update(['order_code' => 'ORD' . date('YmdHis', strtotime($order->created_at)) . strtoupper(substr(uniqid(), -4))]);
        }

        // Thêm unique constraint nếu chưa có
        if (!$this->hasUniqueConstraint('orders', 'order_code')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unique('order_code');
            });
        }
    }

    private function hasUniqueConstraint($table, $column): bool
    {
        $indexes = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND CONSTRAINT_NAME != 'PRIMARY'", [$table, $column]);
        return count($indexes) > 0;
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['order_code']);
            $table->dropColumn('order_code');
        });
    }
};
