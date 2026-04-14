<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class IngredientSeeder extends Seeder
{
    public function run(): void
    {
        $ingredients = [
            // Hải sản & Thịt
            ['name' => 'Cá hồi tươi', 'unit' => 'kg', 'price' => 350000, 'stock_quantity' => 10.5, 'reorder_level' => 3],
            ['name' => 'Bạch tuộc', 'unit' => 'kg', 'price' => 180000, 'stock_quantity' => 5.0, 'reorder_level' => 2],
            ['name' => 'Thịt ba chỉ bò Mỹ', 'unit' => 'kg', 'price' => 180000, 'stock_quantity' => 20.0, 'reorder_level' => 5],
            ['name' => 'Thịt ba chỉ heo', 'unit' => 'kg', 'price' => 120000, 'stock_quantity' => 15.0, 'reorder_level' => 5],
            ['name' => 'Lươn Nhật', 'unit' => 'kg', 'price' => 600000, 'stock_quantity' => 4.0, 'reorder_level' => 1],
            ['name' => 'Cánh gà', 'unit' => 'kg', 'price' => 80000, 'stock_quantity' => 12.0, 'reorder_level' => 3],

            // Rau củ & Đồ khô
            ['name' => 'Gạo dẻo Nhật Bản', 'unit' => 'kg', 'price' => 40000, 'stock_quantity' => 50.0, 'reorder_level' => 10],
            ['name' => 'Đậu nành Nhật (Edamame)', 'unit' => 'kg', 'price' => 65000, 'stock_quantity' => 8.0, 'reorder_level' => 2],
            ['name' => 'Kim chi cải thảo', 'unit' => 'kg', 'price' => 50000, 'stock_quantity' => 15.0, 'reorder_level' => 3],
            ['name' => 'Bánh gạo (Tteok)', 'unit' => 'kg', 'price' => 45000, 'stock_quantity' => 10.0, 'reorder_level' => 2],
            ['name' => 'Mì Ramen', 'unit' => 'kg', 'price' => 60000, 'stock_quantity' => 15.0, 'reorder_level' => 4],
            ['name' => 'Mì Udon', 'unit' => 'kg', 'price' => 55000, 'stock_quantity' => 10.0, 'reorder_level' => 3],
            ['name' => 'Rong biển lá', 'unit' => 'gói', 'price' => 35000, 'stock_quantity' => 50, 'reorder_level' => 10],

            // Gia vị & Đồ uống
            ['name' => 'Sốt Gochujang', 'unit' => 'kg', 'price' => 90000, 'stock_quantity' => 5.0, 'reorder_level' => 1],
            ['name' => 'Rượu Soju truyền thống', 'unit' => 'chai', 'price' => 45000, 'stock_quantity' => 100, 'reorder_level' => 20],
            ['name' => 'Bột Matcha', 'unit' => 'kg', 'price' => 400000, 'stock_quantity' => 2.0, 'reorder_level' => 0.5],
        ];

        // ĐÃ SỬA: Dùng updateOrInsert thay vì insert
        foreach ($ingredients as $ingredient) {
            DB::table('ingredients')->updateOrInsert(
                ['name' => $ingredient['name']], // Điều kiện tìm kiếm (tìm theo tên)
                [
                    'unit' => $ingredient['unit'],
                    'price' => $ingredient['price'], // Nó sẽ cập nhật giá mới vào đây
                    'stock_quantity' => $ingredient['stock_quantity'],
                    'reorder_level' => $ingredient['reorder_level'],
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}
