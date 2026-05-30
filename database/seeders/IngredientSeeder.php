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
            ['name' => 'Cá hồi tươi', 'unit' => 'kg', 'category' => 'meat_seafood', 'price' => 350000, 'stock_quantity' => 10.5, 'reorder_level' => 3],
            ['name' => 'Bạch tuộc', 'unit' => 'kg', 'category' => 'meat_seafood', 'price' => 180000, 'stock_quantity' => 5.0, 'reorder_level' => 2],
            ['name' => 'Thịt ba chỉ bò Mỹ', 'unit' => 'kg', 'category' => 'meat_seafood', 'price' => 180000, 'stock_quantity' => 20.0, 'reorder_level' => 5],
            ['name' => 'Thịt ba chỉ heo', 'unit' => 'kg', 'category' => 'meat_seafood', 'price' => 120000, 'stock_quantity' => 15.0, 'reorder_level' => 5],
            ['name' => 'Lươn Nhật', 'unit' => 'kg', 'category' => 'meat_seafood', 'price' => 600000, 'stock_quantity' => 4.0, 'reorder_level' => 1],
            ['name' => 'Cánh gà', 'unit' => 'kg', 'category' => 'meat_seafood', 'price' => 80000, 'stock_quantity' => 12.0, 'reorder_level' => 3],

            // Rau củ & Đồ khô
            ['name' => 'Gạo dẻo Nhật Bản', 'unit' => 'kg', 'category' => 'carbs_dry', 'price' => 40000, 'stock_quantity' => 50.0, 'reorder_level' => 10],
            ['name' => 'Đậu nành Nhật (Edamame)', 'unit' => 'kg', 'category' => 'vegetables', 'price' => 65000, 'stock_quantity' => 8.0, 'reorder_level' => 2],
            ['name' => 'Kim chi cải thảo', 'unit' => 'kg', 'category' => 'vegetables', 'price' => 50000, 'stock_quantity' => 15.0, 'reorder_level' => 3],
            ['name' => 'Bánh gạo (Tteok)', 'unit' => 'kg', 'category' => 'carbs_dry', 'price' => 45000, 'stock_quantity' => 10.0, 'reorder_level' => 2],
            ['name' => 'Mì Ramen', 'unit' => 'kg', 'category' => 'carbs_dry', 'price' => 60000, 'stock_quantity' => 15.0, 'reorder_level' => 4],
            ['name' => 'Mì Udon', 'unit' => 'kg', 'category' => 'carbs_dry', 'price' => 55000, 'stock_quantity' => 10.0, 'reorder_level' => 3],
            ['name' => 'Rong biển lá', 'unit' => 'gói', 'category' => 'carbs_dry', 'price' => 35000, 'stock_quantity' => 50, 'reorder_level' => 10],

            // Gia vị & Đồ uống
            ['name' => 'Sốt Gochujang', 'unit' => 'kg', 'category' => 'condiments', 'price' => 90000, 'stock_quantity' => 5.0, 'reorder_level' => 1],
            ['name' => 'Rượu Soju truyền thống', 'unit' => 'chai', 'category' => 'beverages', 'price' => 45000, 'stock_quantity' => 100, 'reorder_level' => 20],
            ['name' => 'Bột Matcha', 'unit' => 'kg', 'category' => 'dessert', 'price' => 400000, 'stock_quantity' => 2.0, 'reorder_level' => 0.5],
            ['name' => 'Bột mì/Bột bánh xèo', 'unit' => 'kg', 'category' => 'dessert', 'price' => 45000, 'stock_quantity' => 10.0, 'reorder_level' => 2],
            ['name' => 'Bắp cải', 'unit' => 'kg', 'category' => 'vegetables', 'price' => 20000, 'stock_quantity' => 20.0, 'reorder_level' => 5],
            ['name' => 'Vỏ bánh Mandu', 'unit' => 'gói', 'category' => 'carbs_dry', 'price' => 30000, 'stock_quantity' => 20, 'reorder_level' => 5],
            ['name' => 'Đậu hũ non', 'unit' => 'khối', 'category' => 'vegetables', 'price' => 10000, 'stock_quantity' => 30, 'reorder_level' => 10],
            ['name' => 'Sườn non heo', 'unit' => 'kg', 'category' => 'meat_seafood', 'price' => 160000, 'stock_quantity' => 10.0, 'reorder_level' => 3],
            ['name' => 'Sốt tương đen', 'unit' => 'kg', 'category' => 'condiments', 'price' => 110000, 'stock_quantity' => 5.0, 'reorder_level' => 1],
            ['name' => 'Rượu Soju vị nho', 'unit' => 'chai', 'category' => 'beverages', 'price' => 48000, 'stock_quantity' => 50, 'reorder_level' => 10],
            ['name' => 'Rượu gạo Makgeolli', 'unit' => 'chai', 'category' => 'beverages', 'price' => 65000, 'stock_quantity' => 40, 'reorder_level' => 10],
            ['name' => 'Rượu Sake', 'unit' => 'chai', 'category' => 'beverages', 'price' => 450000, 'stock_quantity' => 10, 'reorder_level' => 2],
            ['name' => 'Nước mơ Choya', 'unit' => 'chai', 'category' => 'beverages', 'price' => 350000, 'stock_quantity' => 15, 'reorder_level' => 3],
            ['name' => 'Bột nếp làm Mochi', 'unit' => 'kg', 'category' => 'dessert', 'price' => 55000, 'stock_quantity' => 5.0, 'reorder_level' => 1],
            ['name' => 'Đậu đỏ sên đường', 'unit' => 'kg', 'category' => 'dessert', 'price' => 85000, 'stock_quantity' => 5.0, 'reorder_level' => 1],
            ['name' => 'Kem cá đóng gói', 'unit' => 'cái', 'category' => 'dessert', 'price' => 18000, 'stock_quantity' => 30, 'reorder_level' => 5],
            ['name' => 'Sữa tươi (làm Bingsu)', 'unit' => 'lít', 'category' => 'dessert', 'price' => 30000, 'stock_quantity' => 20.0, 'reorder_level' => 5],
        ];

        // ĐÃ SỬA: Dùng updateOrInsert thay vì insert
        foreach ($ingredients as $ingredient) {
            DB::table('ingredients')->updateOrInsert(
                ['name' => $ingredient['name']], // Điều kiện tìm kiếm (tìm theo tên)
                [
                    'unit' => $ingredient['unit'],
                    'category' => $ingredient['category'],
                    'price' => $ingredient['price'], // Nó sẽ cập nhật giá mới vào đây
                    'stock_quantity' => $ingredient['stock_quantity'],
                    'reorder_level' => $ingredient['reorder_level'],
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}
