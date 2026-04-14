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

    // 1. Cập nhật thông tin món ăn (Đã thêm validate cho is_featured và is_daily_special)
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'is_featured' => 'nullable|boolean',       // Thêm validate
            'is_daily_special' => 'nullable|boolean',  // Thêm validate
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

    // 2. Xóa món ăn
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

    // 3. Lấy danh sách món ăn
    public function getMenuItems()
    {
        $menuItems = MenuItem::with('category:id,name')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $menuItems
        ]);
    }

    // 4. Cập nhật trạng thái kinh doanh
    public function updateStatus(Request $request, $id)
    {
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

    // 5. Tạo mới món ăn (Đã thêm validate cho 2 cờ nổi bật)
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_featured' => 'nullable|boolean',
            'is_daily_special' => 'nullable|boolean',
        ]);

        $data = $request->all();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('menu_images', 'public');
            $data['img_url'] = $path;
        }

        // Đảm bảo giá trị mặc định nếu không truyền lên
        $data['is_featured'] = $request->is_featured ?? false;
        $data['is_daily_special'] = $request->is_daily_special ?? false;

        $menuItem = MenuItem::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Thêm món ăn thành công!',
            'data' => $menuItem
        ]);
    }

    // =========================================================================
    // CÁC HÀM MỚI BỔ SUNG
    // =========================================================================

    /**
   
     * Thích hợp cho các nút Toggle Switch trên bảng quản trị
     */
    // 6. Cập nhật nhanh cờ "Món đặc sắc" hoặc "Món ngon mỗi ngày"
    public function toggleHighlights(Request $request, $id)
    {
        $request->validate([
            'is_featured' => 'nullable|boolean',
            'is_daily_special' => 'nullable|boolean',
        ]);

        $menuItem = MenuItem::find($id);

        if (!$menuItem) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy món ăn'], 404);
        }

        // Chỉ cập nhật trường nào được gửi lên
        if ($request->has('is_featured')) {
            $menuItem->is_featured = $request->is_featured;
        }

        if ($request->has('is_daily_special')) {
            $menuItem->is_daily_special = $request->is_daily_special;
        }

        $menuItem->save();

        return response()->json([
            'success' => true,
            'message' => 'Cập nhật nhãn món ăn thành công!',
            'data' => $menuItem
        ]);
    }

    /**
     * Dành cho API Client: Lấy danh sách Món đặc sắc đang bán
     */
    public function getFeaturedItems()
    {
        $items = MenuItem::with('category:id,name')
            ->where('is_featured', 1)
            ->where('status', MenuItem::STATUS_ACTIVE) // Đảm bảo món đang còn bán
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * Dành cho API Client: Lấy danh sách Món ngon mỗi ngày đang bán
     */
    public function getDailySpecialItems()
    {
        $items = MenuItem::with('category:id,name')
            ->where('is_daily_special', 1)
            ->where('status', MenuItem::STATUS_ACTIVE) // Đảm bảo món đang còn bán
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
}
