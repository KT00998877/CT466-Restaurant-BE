<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Menu Permissions
            ['name' => 'Xem Dashboard', 'slug' => 'dashboard.view', 'description' => 'Xem trang tổng quan', 'type' => 'menu', 'icon' => 'speedometer2', 'route' => '/admin/dashboard'],
            ['name' => 'Quản Lý Thực Đơn', 'slug' => 'menu.manage', 'description' => 'Quản lý thực đơn nhà hàng', 'type' => 'menu', 'icon' => 'card-list', 'route' => '/admin/menu'],
            ['name' => 'Quản Lý Món Ăn', 'slug' => 'dishes.manage', 'description' => 'Quản lý tiến độ nấu các món', 'type' => 'menu', 'icon' => 'egg', 'route' => '/admin/dishes'],
            ['name' => 'Quản Lý Đơn Hàng', 'slug' => 'orders.manage', 'description' => 'Quản lý hóa đơn bán hàng', 'type' => 'menu', 'icon' => 'receipt', 'route' => '/admin/orders'],
            ['name' => 'Quản Lý Đặt Bàn', 'slug' => 'reservations.manage', 'description' => 'Quản lý đặt bàn', 'type' => 'menu', 'icon' => 'calendar-check', 'route' => '/admin/reservations'],
            ['name' => 'Quản Lý Tài Khoản', 'slug' => 'users.manage', 'description' => 'Quản lý tài khoản nhân viên', 'type' => 'menu', 'icon' => 'people', 'route' => '/admin/users'],
            ['name' => 'Quản Lý Nguyên Liệu', 'slug' => 'ingredients.manage', 'description' => 'Quản lý danh sách nguyên liệu', 'type' => 'menu', 'icon' => 'box-seam', 'route' => '/admin/ingredients'],
            ['name' => 'Xuất Nhập Kho', 'slug' => 'warehouse.operations', 'description' => 'Quản lý xuất nhập kho nguyên liệu', 'type' => 'menu', 'icon' => 'arrow-left-right', 'route' => '/admin/warehouse-operations'],
            ['name' => 'Báo Cáo Kho', 'slug' => 'warehouse.reports', 'description' => 'Xem báo cáo kho nguyên liệu', 'type' => 'menu', 'icon' => 'file-earmark-text', 'route' => '/admin/warehouse-report'],
            ['name' => 'Xem Kho Hàng', 'slug' => 'inventory.view', 'description' => 'Xem chi tiết kho hàng', 'type' => 'menu', 'icon' => 'bar-chart', 'route' => '/admin/inventory'],
            ['name' => 'Quản Lý Liên Hệ', 'slug' => 'contacts.manage', 'description' => 'Quản lý tin nhắn liên hệ', 'type' => 'menu', 'icon' => 'envelope', 'route' => '/admin/contacts'],
            ['name' => 'Xem Báo Cáo', 'slug' => 'reports.view', 'description' => 'Xem báo cáo doanh thu', 'type' => 'menu', 'icon' => 'graph-up', 'route' => '/admin/reports'],
            ['name' => 'Quản Lý Quyền', 'slug' => 'permissions.manage', 'description' => 'Phân quyền chức năng cho nhân viên', 'type' => 'menu', 'icon' => 'shield-lock', 'route' => '/admin/permissions'],

            // API Permissions
            ['name' => 'Tạo Đơn Hàng', 'slug' => 'orders.create', 'description' => 'API tạo đơn hàng mới', 'type' => 'api', 'route' => 'POST /admin/orders'],
            ['name' => 'Sửa Đơn Hàng', 'slug' => 'orders.edit', 'description' => 'API sửa đơn hàng', 'type' => 'api', 'route' => 'PUT /admin/orders/{id}'],
            ['name' => 'Xóa Đơn Hàng', 'slug' => 'orders.delete', 'description' => 'API xóa đơn hàng', 'type' => 'api', 'route' => 'DELETE /admin/orders/{id}'],
            ['name' => 'Tạo Thực Đơn', 'slug' => 'menu.create', 'description' => 'API tạo mục thực đơn mới', 'type' => 'api', 'route' => 'POST /admin/menu-items'],
            ['name' => 'Sửa Thực Đơn', 'slug' => 'menu.edit', 'description' => 'API sửa thực đơn', 'type' => 'api', 'route' => 'PUT /admin/menu-items/{id}'],
            ['name' => 'Xóa Thực Đơn', 'slug' => 'menu.delete', 'description' => 'API xóa thực đơn', 'type' => 'api', 'route' => 'DELETE /admin/menu-items/{id}'],
            ['name' => 'Tạo Nguyên Liệu', 'slug' => 'ingredients.create', 'description' => 'API thêm nguyên liệu mới', 'type' => 'api', 'route' => 'POST /admin/ingredients'],
            ['name' => 'Sửa Nguyên Liệu', 'slug' => 'ingredients.edit', 'description' => 'API sửa nguyên liệu', 'type' => 'api', 'route' => 'PUT /admin/ingredients/{id}'],
            ['name' => 'Xóa Nguyên Liệu', 'slug' => 'ingredients.delete', 'description' => 'API xóa nguyên liệu', 'type' => 'api', 'route' => 'DELETE /admin/ingredients/{id}'],
            ['name' => 'Xuất Nhập Kho', 'slug' => 'warehouse.transaction', 'description' => 'API ghi nhập/xuất kho', 'type' => 'api', 'route' => 'POST /admin/ingredients/{id}/transaction'],
            ['name' => 'Tạo Báo Cáo Kho', 'slug' => 'warehouse-report.create', 'description' => 'API tạo báo cáo kho mới', 'type' => 'api', 'route' => 'POST /admin/warehouse-reports'],
            ['name' => 'Lưu Báo Cáo Kho', 'slug' => 'warehouse-report.save', 'description' => 'API lưu báo cáo kho', 'type' => 'api', 'route' => 'POST /admin/warehouse-reports'],
            ['name' => 'Tạo Tài Khoản', 'slug' => 'users.create', 'description' => 'API tạo tài khoản nhân viên mới', 'type' => 'api', 'route' => 'POST /admin/users'],
            ['name' => 'Sửa Tài Khoản', 'slug' => 'users.edit', 'description' => 'API sửa tài khoản', 'type' => 'api', 'route' => 'PUT /admin/users/{id}'],
            ['name' => 'Xóa Tài Khoản', 'slug' => 'users.delete', 'description' => 'API xóa tài khoản', 'type' => 'api', 'route' => 'DELETE /admin/users/{id}'],
            ['name' => 'Gán Quyền', 'slug' => 'permissions.grant', 'description' => 'API gán quyền cho nhân viên', 'type' => 'api', 'route' => 'POST /admin/permissions/users/{userId}/grant'],
            ['name' => 'Thu Hồi Quyền', 'slug' => 'permissions.revoke', 'description' => 'API thu hồi quyền', 'type' => 'api', 'route' => 'DELETE /admin/permissions/users/{userId}/revoke/{permissionId}'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }

        $rolePermissions = [
            'admin' => Permission::pluck('id')->all(),
            'cashier' => Permission::whereIn('slug', [
                'dashboard.view',
                'orders.manage',
                'orders.create',
                'orders.edit',
                'reservations.manage',
                'reports.view',
            ])->pluck('id')->all(),
            'waiter' => Permission::whereIn('slug', [
                'orders.manage',
                'orders.create',
                'reservations.manage',
            ])->pluck('id')->all(),
            'kitchen' => Permission::whereIn('slug', [
                'dishes.manage',
                'ingredients.manage',
                'ingredients.create',
                'ingredients.edit',
                'warehouse.operations',
                'warehouse-report.create',
            ])->pluck('id')->all(),
            'user' => [],
        ];

        foreach ($rolePermissions as $roleName => $permissionIds) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
