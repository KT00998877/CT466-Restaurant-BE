<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\MenuItem;

class MenuController extends Controller
{
    public function index()
    {
        
        $categories = Category::with('menuItems')->get();

        return response()->json([
            'success' => true,
            'data'    => $categories
        ]);
    }

    // Cập nhật thông tin món ăn
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            // Có thể thêm validate cho description, img_url nếu cần
        ]);

        $menuItem = MenuItem::find($id);

        if (!$menuItem) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        $menuItem->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật món ăn thành công!',
            'data' => $menuItem
        ]);
    }

    // Xóa món ăn
    public function destroy($id)
    {
        $menuItem = MenuItem::find($id);

        if (!$menuItem) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        $menuItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa món ăn thành công!'
        ]);
    }

    public function getMenuItems()
    {
        // Lấy thêm status, price, category_id. Lấy kèm tên danh mục (nếu bạn đã định nghĩa relationship 'category' trong Model MenuItem)
        $menuItems = MenuItem::with('category:id,name')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $menuItems
        ]);
    }

    
    // 2. Thêm hàm mới: Cập nhật trạng thái
    public function updateStatus(Request $request, $id)
    {
        // Validate dữ liệu gửi lên (chỉ cho phép 0, 1, 2)
        $request->validate([
            'status' => 'required|integer|in:0,1,2'
        ]);

        $menuItem = MenuItem::find($id);

        if (!$menuItem) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        $menuItem->status = $request->status;
        $menuItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật trạng thái thành công!',
            'data' => $menuItem
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048' // Validate file ảnh
        ]);

        // Lấy toàn bộ dữ liệu request
        $data = $request->all();

        // Xử lý lưu ảnh nếu có
        if ($request->hasFile('image')) {
            // Lưu file vào thư mục 'menu_images' trong ổ đĩa 'public'
            // Hàm store() sẽ tự động tạo tên file ngẫu nhiên và trả về đường dẫn: "menu_images/ten-file.jpg"
            $path = $request->file('image')->store('menu_images', 'public');

            // Gán đường dẫn này vào cột img_url
            $data['img_url'] = $path;
        }

        // Tạo món ăn mới
        $menuItem = MenuItem::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Thêm món ăn thành công!',
            'data' => $menuItem
        ]);
    }
    
}
