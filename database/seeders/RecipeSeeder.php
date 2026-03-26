<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RecipeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Xóa dữ liệu cũ (nếu có) để tránh bị trùng lặp khi chạy seeder nhiều lần
        DB::table('ingredient_menu_item')->truncate();

        // 2. Lấy danh sách ID ánh xạ theo Tên để code tự động khớp chính xác
        $menuItems = DB::table('menu_items')->pluck('id', 'name');
        $ingredients = DB::table('ingredients')->pluck('id', 'name');

        if ($menuItems->isEmpty() || $ingredients->isEmpty()) {
            return;
        }

        // 3. Khai báo công thức (Tên món ăn => [Tên nguyên liệu => Số lượng cần])
        $recipes = [
            // --- KHAI VỊ & ĂN VẶT ---
            'Đậu nành Nhật luộc (Edamame)' => [
                'Đậu nành Nhật (Edamame)' => 0.2, // 200g
            ],
            'Bạch tuộc nướng (Takoyaki)' => [
                'Bạch tuộc' => 0.05, // 50g bạch tuộc
            ],
            'Kim chi cải thảo' => [
                'Kim chi cải thảo' => 0.15, // 150g
            ],
            'Bánh gạo cay (Tteokbokki)' => [
                'Bánh gạo (Tteok)' => 0.2, // 200g
                'Sốt Gochujang' => 0.05, // 50g sốt
            ],

            // --- MÓN CHÍNH ---
            'Sushi cá hồi' => [
                'Gạo dẻo Nhật Bản' => 0.1, // 100g gạo
                'Cá hồi tươi' => 0.05, // 50g cá
            ],
            'Sashimi tổng hợp (Lớn)' => [
                'Cá hồi tươi' => 0.15,
                'Bạch tuộc' => 0.1,
            ],
            'Mì Ramen Tonkotsu' => [
                'Mì Ramen' => 0.15, // 150g mì
                'Thịt ba chỉ heo' => 0.05, // 50g thịt xá xíu
            ],
            'Mì Udon hải sản' => [
                'Mì Udon' => 0.15,
                'Bạch tuộc' => 0.05,
            ],
            'Cơm bò nướng (Gyudon)' => [
                'Gạo dẻo Nhật Bản' => 0.15,
                'Thịt ba chỉ bò Mỹ' => 0.1,
            ],
            'Lươn nướng Nhật (Unagi)' => [
                'Gạo dẻo Nhật Bản' => 0.15,
                'Lươn Nhật' => 0.15,
            ],
            'Cơm cuộn truyền thống (Kimbap)' => [
                'Gạo dẻo Nhật Bản' => 0.1,
                'Rong biển lá' => 1, // 1 gói/lá
            ],
            'Cơm trộn thố đá (Bibimbap)' => [
                'Gạo dẻo Nhật Bản' => 0.15,
                'Thịt ba chỉ bò Mỹ' => 0.05,
                'Sốt Gochujang' => 0.02,
            ],
            'Thịt ba chỉ bò Mỹ nướng' => [
                'Thịt ba chỉ bò Mỹ' => 0.25, // 250g
            ],
            'Thịt heo xào cay (Jeyuk Bokkeum)' => [
                'Thịt ba chỉ heo' => 0.2,
                'Sốt Gochujang' => 0.05,
            ],
            'Gà rán sốt cay ngọt Hàn Quốc' => [
                'Cánh gà' => 0.3, // 300g
                'Sốt Gochujang' => 0.05,
            ],

            // --- ĐỒ UỐNG & TRÁNG MIỆNG ---
            'Rượu Soju vị truyền thống' => [
                'Rượu Soju truyền thống' => 1, // 1 chai
            ],
            'Trà xanh Matcha lạnh' => [
                'Bột Matcha' => 0.01, // 10g bột
            ],

            // --- COMBO ---
            'Combo Nhậu Hàn Quốc' => [
                'Thịt ba chỉ bò Mỹ' => 0.25,
                'Rượu Soju truyền thống' => 2, // 2 chai
                'Kim chi cải thảo' => 0.15,
            ],
            'Combo Sushi Cặp Đôi' => [
                'Cá hồi tươi' => 0.2,
                'Bạch tuộc' => 0.1,
                'Gạo dẻo Nhật Bản' => 0.2,
                'Bột Matcha' => 0.02,
            ],
        ];

        // 4. Tiến hành Insert vào database
        $insertData = [];
        $now = Carbon::now();

        foreach ($recipes as $menuItemName => $ingredientList) {
            // Kiểm tra xem Món ăn có tồn tại trong DB không
            if (isset($menuItems[$menuItemName])) {
                $menuItemId = $menuItems[$menuItemName];

                foreach ($ingredientList as $ingredientName => $qty) {
                    // Kiểm tra xem Nguyên liệu có tồn tại trong DB không
                    if (isset($ingredients[$ingredientName])) {
                        $insertData[] = [
                            'menu_item_id' => $menuItemId,
                            'ingredient_id' => $ingredients[$ingredientName],
                            'quantity_required' => $qty,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }
            }
        }

        // Chèn dữ liệu theo lô để tối ưu tốc độ
        if (!empty($insertData)) {
            DB::table('ingredient_menu_item')->insert($insertData);
        }
    }
}
