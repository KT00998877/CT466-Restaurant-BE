<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Thêm dòng này để dùng DB facade
use Carbon\Carbon; // Dùng để lấy thời gian hiện tại cho created_at

class CategorySeeder extends Seeder
{
    public function run(): void
    {
       
        $categories = [
            ['name' => 'Món Khai Vị'],
            ['name' => 'Món Chính'],
            ['name' => 'Đồ Uống'],
            ['name' => 'Tráng Miệng'],
            ['name' => 'Combo Khuyến Mãi'],
        ];

        // Lặp qua mảng và chèn vào database
        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'name' => $category['name'],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
