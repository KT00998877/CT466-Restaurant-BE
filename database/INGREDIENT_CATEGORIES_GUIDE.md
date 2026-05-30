## 📋 Hướng Dẫn Phân Loại Nguyên Liệu Theo Nhóm Thực Phẩm

### 🎯 Tính Năng Được Thêm

Giao diện Quản Lý Kho Nguyên Liệu đã được cập nhật để hỗ trợ phân loại nguyên liệu theo **6 nhóm thực phẩm chính**:

1. **Thịt & Hải sản** (Meat & Seafood)
    - Cá hồi tươi, Bạch tuộc, Thịt ba chỉ bò Mỹ, Thịt ba chỉ heo, Lươn Nhật, Cánh gà, Sườn non heo

2. **Tinh bột & Đồ khô** (Carbs & Dry Goods)
    - Gạo dẻo Nhật Bản, Bánh gạo (Tteok), Mì Ramen, Mì Udon, Rong biển lá, Vỏ bánh Mandu

3. **Rau củ & Chế phẩm** (Vegetables & Processed)
    - Đậu nành Nhật (Edamame), Kim chi cải thảo, Bắp cải, Đậu hũ non

4. **Gia vị & Nước sốt** (Condiments & Sauces)
    - Sốt Gochujang, Sốt tương đen

5. **Đồ uống** (Beverages)
    - Rượu Soju truyền thống, Rượu Soju vị nho, Rượu gạo Makgeolli, Rượu Sake, Nước mơ Choya

6. **Tráng miệng & Pha chế** (Dessert & Baking)
    - Bột Matcha, Bột mì/Bột bánh xèo, Bột nếp làm Mochi, Đậu đỏ sên đường, Kem cá đóng gói, Sữa tươi

---

### 🔧 Thay Đổi Kỹ Thuật

#### **Frontend (Vue.js)**

**File**: `src/views/admin/IngredientManager.vue`

✅ Thêm dropdown chọn **Nhóm thực phẩm** trong form
✅ Thêm **filter buttons** để lọc nguyên liệu theo nhóm
✅ Hiển thị bảng **nhóm hóa theo category** với tiêu đề phân loại
✅ Thêm cột **"Nhóm thực phẩm"** trong bảng
✅ Thêm logic filtering dùng computed properties

#### **Backend (Laravel)**

**Files**:

- `database/migrations/2026_05_30_000000_add_category_to_ingredients_table.php` (Migration mới)
- `app/Models/Ingredient.php` (Model được cập nhật)
- `app/Http/Controllers/Api/IngredientController.php` (Controller được cập nhật)

✅ Tạo migration để thêm cột `category` vào bảng `ingredients`
✅ Cập nhật `$fillable` trong Model để bao gồm `category`
✅ Cập nhật validation trong `store()` và `update()` để xác thực `category`

---

### 📦 Các Bước Thực Hiện (Deployment)

1. **Chạy Migration** (Backend):

    ```bash
    php artisan migrate
    ```

    Lệnh này sẽ tạo cột `category` trong bảng `ingredients`

2. **Build Frontend** (nếu cần):

    ```bash
    npm run build
    ```

3. **Kiểm Tra Database**:
    - Bảng `ingredients` sẽ có cột mới: `category` (nullable string)
    - Dữ liệu cũ sẽ giữ nguyên (category = null)

---

### 💡 Cách Sử Dụng

**Thêm nguyên liệu mới:**

1. Chọn **Nhóm thực phẩm** từ dropdown
2. Nhập tên nguyên liệu, đơn vị, tồn kho, mức cảnh báo
3. Nhấn "Thêm mới"

**Lọc nguyên liệu:**

- Nhấn nút của nhóm thực phẩm để chỉ hiển thị nguyên liệu trong nhóm đó
- Nhấn "Tất cả" để xem toàn bộ

**Bảng sẽ hiển thị:**

- Tiêu đề nhóm (ví dụ: "📁 Thịt & Hải sản (Meat & Seafood)")
- Các nguyên liệu thuộc nhóm đó
- Thông tin tồn kho, mức cảnh báo, và thao tác sửa/xóa

---

### 📝 Dữ Liệu Cũ

- Nguyên liệu hiện có sẽ có `category = null`
- Bạn có thể sửa từng nguyên liệu để gán chúng vào nhóm phù hợp
- Hoặc sử dụng script SQL để cập nhật hàng loạt (nếu cần)

---

### 🎨 Giao Diện

- **Form**: Grid 2 cột, dễ nhập liệu
- **Filter buttons**: Hiển thị tất cả nhóm, có highlight khi chọn
- **Bảng**: Nhóm hóa tự động, tiêu đề nhóm với biểu tượng 📁
- **Responsive**: Tương thích với các thiết bị khác nhau
