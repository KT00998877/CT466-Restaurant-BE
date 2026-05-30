# 📝 Hướng Dẫn Cập Nhật Category Cho Ingredients

## ✅ 3 Cách Cập Nhật Dữ Liệu

### **Cách 1: Chạy Route Tạm Thời (⭐ Easiest)**

1. Mở trình duyệt và truy cập:

    ```
    http://localhost:8000/update-ingredient-categories
    ```

2. Bạn sẽ nhận được response JSON:

    ```json
    {
      "success": true,
      "message": "✅ Đã cập nhật 29 nguyên liệu với categories.",
      "data": { ... }
    }
    ```

3. ✨ Xong! Tất cả nguyên liệu đã có category.

---

### **Cách 2: Chạy Seeder**

```bash
# Cách 2a: Chạy lại toàn bộ seeder
php artisan db:seed

# Cách 2b: Chỉ chạy IngredientSeeder
php artisan db:seed --class=IngredientSeeder
```

**Lưu ý:** Seeder sẽ sử dụng `updateOrInsert`, nên:

- Nguyên liệu cũ sẽ được cập nhật category
- Nguyên liệu mới sẽ được thêm vào

---

### **Cách 3: Chạy SQL Trực Tiếp**

**Trong MySQL Workbench, phpMyAdmin, hoặc terminal:**

```bash
# Từ terminal (nếu cài MySQL)
mysql -u root -p your_database_name < database/update_categories.sql
```

**Hoặc sao chép code từ:** `backend/database/update_categories.sql`

---

## 📊 Danh Sách Category Được Cập Nhật

| Category ID    | Tên Tiếng Việt        | Số Lượng |
| -------------- | --------------------- | -------- |
| `meat_seafood` | Thịt & Hải sản        | 7        |
| `carbs_dry`    | Tinh bột & Đồ khô     | 6        |
| `vegetables`   | Rau củ & Chế phẩm     | 4        |
| `condiments`   | Gia vị & Nước sốt     | 2        |
| `beverages`    | Đồ uống               | 5        |
| `dessert`      | Tráng miệng & Pha chế | 6        |

**Tổng cộng: 30 nguyên liệu**

---

## 🔍 Kiểm Tra Dữ Liệu

### Cách 1: Qua API

```bash
curl http://localhost:8000/api/admin/ingredients
```

### Cách 2: Truy vấn SQL

```sql
SELECT id, name, category, unit, stock_quantity FROM ingredients ORDER BY category;
```

### Cách 3: Qua Giao Diện

- Vào **Admin Panel → Quản lý Kho Nguyên Liệu**
- Bảng sẽ hiển thị nhóm hóa theo category ✨

---

## 🗂️ File Liên Quan

- ✅ `backend/routes/web.php` - Route tạm thời
- ✅ `backend/database/seeders/IngredientSeeder.php` - Seeder được cập nhật
- ✅ `backend/database/update_categories.sql` - Script SQL
- ✅ `backend/database/migrations/2026_05_30_000000_add_category_to_ingredients_table.php` - Migration

---

## 🎯 Bước Tiếp Theo

1. ✅ **Chạy cập nhật** bằng một trong 3 cách trên
2. ✅ **Kiểm tra dữ liệu** trong giao diện hoặc API
3. ✅ **Xóa route tạm thời** (tùy chọn - sau khi hoàn tất)
    - Xóa hàm `Route::get('/update-ingredient-categories', ...)` từ `routes/web.php`

---

## ⚡ Tips

- **Route tạm thời** có thể bị xóa sau khi dữ liệu đã được cập nhật
- **Seeder** có thể chạy nhiều lần mà không có vấn đề (updateOrInsert)
- **SQL script** là cách nhanh nhất nếu có quyền truy cập trực tiếp database
