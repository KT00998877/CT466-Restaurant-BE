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
            ['name' => 'Cá hồi tươi', 'unit' => 'kg', 'stock_quantity' => 10.5, 'reorder_level' => 3],
            ['name' => 'Bạch tuộc', 'unit' => 'kg', 'stock_quantity' => 5.0, 'reorder_level' => 2],
            ['name' => 'Thịt ba chỉ bò Mỹ', 'unit' => 'kg', 'stock_quantity' => 20.0, 'reorder_level' => 5],
            ['name' => 'Thịt ba chỉ heo', 'unit' => 'kg', 'stock_quantity' => 15.0, 'reorder_level' => 5],
            ['name' => 'Lươn Nhật', 'unit' => 'kg', 'stock_quantity' => 4.0, 'reorder_level' => 1],
            ['name' => 'Cánh gà', 'unit' => 'kg', 'stock_quantity' => 12.0, 'reorder_level' => 3],

            // Rau củ & Đồ khô
            ['name' => 'Gạo dẻo Nhật Bản', 'unit' => 'kg', 'stock_quantity' => 50.0, 'reorder_level' => 10],
            ['name' => 'Đậu nành Nhật (Edamame)', 'unit' => 'kg', 'stock_quantity' => 8.0, 'reorder_level' => 2],
            ['name' => 'Kim chi cải thảo', 'unit' => 'kg', 'stock_quantity' => 15.0, 'reorder_level' => 3],
            ['name' => 'Bánh gạo (Tteok)', 'unit' => 'kg', 'stock_quantity' => 10.0, 'reorder_level' => 2],
            ['name' => 'Mì Ramen', 'unit' => 'kg', 'stock_quantity' => 15.0, 'reorder_level' => 4],
            ['name' => 'Mì Udon', 'unit' => 'kg', 'stock_quantity' => 10.0, 'reorder_level' => 3],
            ['name' => 'Rong biển lá', 'unit' => 'gói', 'stock_quantity' => 50, 'reorder_level' => 10],

            // Gia vị & Đồ uống
            ['name' => 'Sốt Gochujang', 'unit' => 'kg', 'stock_quantity' => 5.0, 'reorder_level' => 1],
            ['name' => 'Rượu Soju truyền thống', 'unit' => 'chai', 'stock_quantity' => 100, 'reorder_level' => 20],
            ['name' => 'Bột Matcha', 'unit' => 'kg', 'stock_quantity' => 2.0, 'reorder_level' => 0.5],
        ];

        foreach ($ingredients as $ingredient) {
            DB::table('ingredients')->insert(array_merge($ingredient, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]));
        }
    }
}
