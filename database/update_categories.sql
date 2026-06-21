-- Update categories cho các nguyên liệu hiện có

-- Thịt & Hải sản (Meat & Seafood)
update ingredients
   set
   category = 'meat_seafood'
 where name in ( 'Cá hồi tươi',
                 'Bạch tuộc',
                 'Thịt ba chỉ bò Mỹ',
                 'Thịt ba chỉ heo',
                 'Lươn Nhật',
                 'Cánh gà',
                 'Sườn non heo' );

-- Tinh bột & Đồ khô (Carbs & Dry Goods)
update ingredients
   set
   category = 'carbs_dry'
 where name in ( 'Gạo dẻo Nhật Bản',
                 'Bánh gạo (Tteok)',
                 'Mì Ramen',
                 'Mì Udon',
                 'Rong biển lá',
                 'Vỏ bánh Mandu' );

-- Rau củ & Chế phẩm (Vegetables & Processed)
update ingredients
   set
   category = 'vegetables'
 where name in ( 'Đậu nành Nhật (Edamame)',
                 'Kim chi cải thảo',
                 'Bắp cải',
                 'Đậu hũ non' );

-- Gia vị & Nước sốt (Condiments & Sauces)
update ingredients
   set
   category = 'condiments'
 where name in ( 'Sốt Gochujang',
                 'Sốt tương đen' );

-- Đồ uống (Beverages)
update ingredients
   set
   category = 'beverages'
 where name in ( 'Rượu Soju truyền thống',
                 'Rượu Soju vị nho',
                 'Rượu gạo Makgeolli',
                 'Rượu Sake',
                 'Nước mơ Choya' );

-- Tráng miệng & Pha chế (Dessert & Baking)
update ingredients
   set
   category = 'dessert'
 where name in ( 'Bột Matcha',
                 'Bột mì/Bột bánh xèo',
                 'Bột nếp làm Mochi',
                 'Đậu đỏ sên đường',
                 'Kem cá đóng gói',
                 'Sữa tươi (làm Bingsu)' );

-- Kiểm tra kết quả
select category,
       count(*) as total,
       group_concat(
          name,
          ', '
       ) as ingredients
  from ingredients
 group by category
 order by category;