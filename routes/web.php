<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Backend connected'
    ]);
});

// Route tạm thời để cập nhật categories cho ingredients
Route::get('/update-ingredient-categories', function () {
    try {
        $ingredientCategories = [
            // Thịt & Hải sản (Meat & Seafood)
            'Cá hồi tươi' => 'meat_seafood',
            'Bạch tuộc' => 'meat_seafood',
            'Thịt ba chỉ bò Mỹ' => 'meat_seafood',
            'Thịt ba chỉ heo' => 'meat_seafood',
            'Lươn Nhật' => 'meat_seafood',
            'Cánh gà' => 'meat_seafood',
            'Sườn non heo' => 'meat_seafood',

            // Tinh bột & Đồ khô (Carbs & Dry Goods)
            'Gạo dẻo Nhật Bản' => 'carbs_dry',
            'Bánh gạo (Tteok)' => 'carbs_dry',
            'Mì Ramen' => 'carbs_dry',
            'Mì Udon' => 'carbs_dry',
            'Rong biển lá' => 'carbs_dry',
            'Vỏ bánh Mandu' => 'carbs_dry',

            // Rau củ & Chế phẩm (Vegetables & Processed)
            'Đậu nành Nhật (Edamame)' => 'vegetables',
            'Kim chi cải thảo' => 'vegetables',
            'Bắp cải' => 'vegetables',
            'Đậu hũ non' => 'vegetables',

            // Gia vị & Nước sốt (Condiments & Sauces)
            'Sốt Gochujang' => 'condiments',
            'Sốt tương đen' => 'condiments',

            // Đồ uống (Beverages)
            'Rượu Soju truyền thống' => 'beverages',
            'Rượu Soju vị nho' => 'beverages',
            'Rượu gạo Makgeolli' => 'beverages',
            'Rượu Sake' => 'beverages',
            'Nước mơ Choya' => 'beverages',

            // Tráng miệng & Pha chế (Dessert & Baking)
            'Bột Matcha' => 'dessert',
            'Bột mì/Bột bánh xèo' => 'dessert',
            'Bột nếp làm Mochi' => 'dessert',
            'Đậu đỏ sên đường' => 'dessert',
            'Kem cá đóng gói' => 'dessert',
            'Sữa tươi (làm Bingsu)' => 'dessert',
        ];

        $updated = 0;
        foreach ($ingredientCategories as $name => $category) {
            $result = DB::table('ingredients')
                ->where('name', $name)
                ->update(['category' => $category]);

            if ($result > 0) {
                $updated += $result;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "✅ Đã cập nhật $updated nguyên liệu với categories.",
            'data' => $ingredientCategories
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ], 500);
    }
});
