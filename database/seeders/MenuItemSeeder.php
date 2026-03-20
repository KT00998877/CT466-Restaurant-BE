<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $menuItems = [

            ['category_id' => 1, 'name' => 'Đậu nành Nhật luộc (Edamame)', 'description' => 'Đậu nành non luộc muối biển, món nhắm bia hoàn hảo.', 'price' => 35000, 'is_combo' => false, 'img_url' => 'menu_images/edamame.jpg'],
            ['category_id' => 1, 'name' => 'Bánh xèo Nhật Bản (Okonomiyaki)', 'description' => 'Bánh xèo hải sản phủ cá bào và sốt mayonnaise.', 'price' => 65000, 'is_combo' => false, 'img_url' => 'menu_images/okonomiyaki.jpg'],
            ['category_id' => 1, 'name' => 'Bạch tuộc nướng (Takoyaki)', 'description' => 'Viên bạch tuộc nướng thơm lừng, giòn rụm.', 'price' => 55000, 'is_combo' => false, 'img_url' => 'menu_images/takoyaki.jpg'],



            ['category_id' => 1, 'name' => 'Kim chi cải thảo', 'description' => 'Kim chi lên men tự nhiên, chua cay chuẩn vị Hàn.', 'price' => 25000, 'is_combo' => false, 'img_url' => 'menu_images/kimchi.jpg'],
            ['category_id' => 1, 'name' => 'Bánh gạo cay (Tteokbokki)', 'description' => 'Bánh gạo dẻo nấu cùng chả cá trong sốt tương ớt Gochujang.', 'price' => 55000, 'is_combo' => false, 'img_url' => 'menu_images/tteokbokki.jpg'],
            ['category_id' => 1, 'name' => 'Há cảo Hàn Quốc hấp (Mandu)', 'description' => 'Há cảo nhân thịt và rau củ, vỏ mỏng dẻo.', 'price' => 45000, 'is_combo' => false, 'img_url' => 'menu_images/mandu.jpg'],




            ['category_id' => 2, 'name' => 'Sushi cá hồi', 'description' => 'Cơm nắm giấm Nhật kèm lát cá hồi tươi sống.', 'price' => 75000, 'is_combo' => false, 'img_url' => 'menu_images/sushi-ca-hoi.jpg'],
            ['category_id' => 2, 'name' => 'Sashimi tổng hợp (Lớn)', 'description' => 'Gồm cá hồi, cá trích ép trứng, bạch tuộc và sò đỏ tươi.', 'price' => 350000, 'is_combo' => false, 'img_url' => 'menu_images/sashimi-tong-hop.jpg'],
            ['category_id' => 2, 'name' => 'Mì Ramen Tonkotsu', 'description' => 'Mì Ramen nước cốt xương heo hầm 12 tiếng, kèm thịt xá xíu.', 'price' => 120000, 'is_combo' => false, 'img_url' => 'menu_images/ramen-tonkotsu.jpg'],
            ['category_id' => 2, 'name' => 'Mì Udon hải sản', 'description' => 'Mì Udon sợi to dẻo cùng tôm, mực trong nước dùng dashi.', 'price' => 110000, 'is_combo' => false, 'img_url' => 'menu_images/udon-hai-san.jpg'],
            ['category_id' => 2, 'name' => 'Cơm bò nướng (Gyudon)', 'description' => 'Cơm dẻo phủ thịt bò ba chỉ cắt lát mỏng xào hành tây.', 'price' => 95000, 'is_combo' => false, 'img_url' => 'menu_images/gyudon.jpg'],
            ['category_id' => 2, 'name' => 'Lươn nướng Nhật (Unagi)', 'description' => 'Lươn nướng than hoa phủ sốt Teriyaki đậm đà.', 'price' => 220000, 'is_combo' => false, 'img_url' => 'menu_images/unagi.jpg'],



            ['category_id' => 2, 'name' => 'Cơm cuộn truyền thống (Kimbap)', 'description' => 'Cơm cuộn rong biển nhân xúc xích, trứng, củ cải muối.', 'price' => 45000, 'is_combo' => false, 'img_url' => 'menu_images/kimbap.jpg'],
            ['category_id' => 2, 'name' => 'Cơm trộn thố đá (Bibimbap)', 'description' => 'Cơm trộn thịt bò, nấm, trứng ốp la phục vụ trong thố nóng.', 'price' => 85000, 'is_combo' => false, 'img_url' => 'menu_images/bibimbap.jpg'],
            ['category_id' => 2, 'name' => 'Thịt ba chỉ bò Mỹ nướng', 'description' => 'Ba chỉ bò nướng xèo xèo, cuộn xà lách và lá mè.', 'price' => 180000, 'is_combo' => false, 'img_url' => 'menu_images/samgyeopsal.jpg'],
            ['category_id' => 2, 'name' => 'Thịt heo xào cay (Jeyuk Bokkeum)', 'description' => 'Thịt ba chỉ xào tương ớt cay nồng, ăn tốn cơm.', 'price' => 120000, 'is_combo' => false, 'img_url' => 'menu_images/jeyuk.jpg'],
            ['category_id' => 2, 'name' => 'Canh kim chi đậu hũ sườn non', 'description' => 'Canh chua cay nóng hổi, nấu cùng đậu hũ non mềm mịn.', 'price' => 95000, 'is_combo' => false, 'img_url' => 'menu_images/canh-kim-chi.jpg'],
            ['category_id' => 2, 'name' => 'Mì tương đen (Jajangmyeon)', 'description' => 'Mì sợi dai trộn sốt tương đen đặc biệt với thịt heo băm.', 'price' => 85000, 'is_combo' => false, 'img_url' => 'menu_images/jajangmyeon.jpg'],
            ['category_id' => 2, 'name' => 'Gà rán sốt cay ngọt Hàn Quốc', 'description' => 'Cánh gà giòn rụm áo lớp sốt mật ong tương ớt dẻo quánh.', 'price' => 150000, 'is_combo' => false, 'img_url' => 'menu_images/ga-ran-cay-ngot.jpg'],



            ['category_id' => 3, 'name' => 'Rượu Soju vị truyền thống', 'description' => 'Rượu Soju nguyên bản (Chamisul/Chum Churum).', 'price' => 65000, 'is_combo' => false, 'img_url' => 'menu_images/soju-truyen-thong.jpg'],
            ['category_id' => 3, 'name' => 'Rượu Soju vị nho', 'description' => 'Soju trái cây dịu ngọt, dễ uống cho phái nữ.', 'price' => 70000, 'is_combo' => false, 'img_url' => 'menu_images/soju-trai-cay.jpg'],
            ['category_id' => 3, 'name' => 'Rượu gạo Hàn Quốc (Makgeolli)', 'description' => 'Rượu gạo nếp lên men tự nhiên, uống lạnh bằng bát.', 'price' => 90000, 'is_combo' => false, 'img_url' => 'menu_images/makgeolli.jpg'],
            ['category_id' => 3, 'name' => 'Rượu Sake Nhật Bản (Bình 150ml)', 'description' => 'Sake vảy vàng hâm nóng hoặc ướp lạnh tùy sở thích.', 'price' => 120000, 'is_combo' => false, 'img_url' => 'menu_images/sake.jpg'],
            ['category_id' => 3, 'name' => 'Nước ép mơ ngâm Choya', 'description' => 'Nước ép mơ xanh giải khát, chua ngọt thanh mát.', 'price' => 45000, 'is_combo' => false, 'img_url' => 'menu_images/choya.jpg'],
            ['category_id' => 3, 'name' => 'Trà xanh Matcha lạnh', 'description' => 'Trà xanh Nhật Bản nguyên chất, thanh lọc cơ thể.', 'price' => 35000, 'is_combo' => false, 'img_url' => 'menu_images/matcha.jpg'],


            ['category_id' => 4, 'name' => 'Bánh Mochi nhân kem trà xanh', 'description' => 'Vỏ mochi dẻo bọc lớp kem matcha mát lạnh bên trong.', 'price' => 35000, 'is_combo' => false, 'img_url' => 'menu_images/mochi.jpg'],
            ['category_id' => 4, 'name' => 'Bingsu đậu đỏ hạt nướng', 'description' => 'Đá bào tuyết sữa tươi phủ đậu đỏ và hạnh nhân nướng.', 'price' => 85000, 'is_combo' => false, 'img_url' => 'menu_images/bingsu-dau-do.jpg'],
            ['category_id' => 4, 'name' => 'Kem cá vị Vani', 'description' => 'Kem ốc quế hình cá nướng, giòn xốp thơm lừng.', 'price' => 25000, 'is_combo' => false, 'img_url' => 'menu_images/kem-ca.jpg'],


            ['category_id' => 5, 'name' => 'Combo Nhậu Hàn Quốc', 'description' => 'Gồm 1 Ba chỉ bò nướng, 2 Soju truyền thống, 1 Kimchi.', 'price' => 299000, 'is_combo' => true, 'img_url' => 'menu_images/combo-nhau-han.jpg'],
            ['category_id' => 5, 'name' => 'Combo Sushi Cặp Đôi', 'description' => 'Gồm 1 Sashimi tổng hợp, 1 Sushi cá hồi, 2 Trà xanh Matcha.', 'price' => 350000, 'is_combo' => true, 'img_url' => 'menu_images/combo-sushi.jpg'],
            ['category_id' => 5, 'name' => 'Combo Gà Rán Khổng Lồ & Bia', 'description' => 'Gồm 1 Gà rán cay ngọt lớn, 1 Tteokbokki, 2 Nước mơ Choya.', 'price' => 259000, 'is_combo' => true, 'img_url' => 'menu_images/combo-ga-ran.jpg'],
        ];


        foreach ($menuItems as $item) {
            DB::table('menu_items')->insert([
                'category_id' => $item['category_id'],
                'name'        => $item['name'],
                'description' => $item['description'],
                'price'       => $item['price'],
                'img_url'     => $item['img_url'],
                'is_combo'    => $item['is_combo'],
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);
        }
    }
}
