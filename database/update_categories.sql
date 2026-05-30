-- Update categories cho các nguyên liệu hiện có

-- Thịt & Hải sản (Meat & Seafood)
UPDATE ingredients SET category = 'meat_seafood' WHERE name IN (
    'Cá hồi tươi', 'Bạch tuộc', 'Thịt ba chỉ bò Mỹ', 'Thịt ba chỉ heo', 
    'Lươn Nhật', 'Cánh gà', 'Sườn non heo'
);

-- Tinh bột & Đồ khô (Carbs & Dry Goods)
UPDATE ingredients SET category = 'carbs_dry' WHERE name IN (
    'Gạo dẻo Nhật Bản', 'Bánh gạo (Tteok)', 'Mì Ramen', 'Mì Udon', 
    'Rong biển lá', 'Vỏ bánh Mandu'
);

-- Rau củ & Chế phẩm (Vegetables & Processed)
UPDATE ingredients SET category = 'vegetables' WHERE name IN (
    'Đậu nành Nhật (Edamame)', 'Kim chi cải thảo', 'Bắp cải', 'Đậu hũ non'
);

-- Gia vị & Nước sốt (Condiments & Sauces)
UPDATE ingredients SET category = 'condiments' WHERE name IN (
    'Sốt Gochujang', 'Sốt tương đen'
);

-- Đồ uống (Beverages)
UPDATE ingredients SET category = 'beverages' WHERE name IN (
    'Rượu Soju truyền thống', 'Rượu Soju vị nho', 'Rượu gạo Makgeolli', 
    'Rượu Sake', 'Nước mơ Choya'
);

-- Tráng miệng & Pha chế (Dessert & Baking)
UPDATE ingredients SET category = 'dessert' WHERE name IN (
    'Bột Matcha', 'Bột mì/Bột bánh xèo', 'Bột nếp làm Mochi', 
    'Đậu đỏ sên đường', 'Kem cá đóng gói', 'Sữa tươi (làm Bingsu)'
);

-- Kiểm tra kết quả
SELECT category, COUNT(*) as total, GROUP_CONCAT(name, ', ') as ingredients 
FROM ingredients 
GROUP BY category 
ORDER BY category;
