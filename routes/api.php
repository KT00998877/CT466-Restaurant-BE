<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\WarehouseReportController;
use App\Http\Controllers\Api\PermissionController;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC ROUTES (Không yêu cầu đăng nhập)
|--------------------------------------------------------------------------
*/

// ── Auth ────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'sendResetOtp']);
Route::post('/forgot-password/verify-otp', [AuthController::class, 'verifyResetOtp']);
Route::post('/forgot-password/update', [AuthController::class, 'updateNewPassword']);

Route::get('/email/verify', [AuthController::class, 'verifyEmail'])->name('verification.verify');

// ── Menu ────────────────────────────────────────────────
Route::get('/menu',                 [MenuController::class, 'index']);
Route::get('/menu-items',           [MenuController::class, 'getMenuItems']);
Route::get('/menu/featured',        [MenuController::class, 'getFeaturedItems']);
Route::get('/menu/daily-specials',  [MenuController::class, 'getDailySpecialItems']);

// ── Đặt bàn & Tra cứu ───────────────────────────────────
Route::get('/tables',                     [ReservationController::class, 'getTables']); // Lọc bằng ?reserved_at=...
Route::get('/tables/check-availability',  [ReservationController::class, 'checkAvailability']);

// ── Khác (Liên hệ, Chatbot) ─────────────────────────────
Route::post('/contacts', [ContactController::class, 'store']);
Route::get('/contacts',  [ContactController::class, 'index']);
Route::post('/chatbot',  [ChatbotController::class, 'handleChat']);


/*
|--------------------------------------------------------------------------
| 2. PROTECTED ROUTES (Yêu cầu đăng nhập - auth:sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // ── 2.1. Xác thực & Tài khoản cá nhân (Customer & General Users) ─────────
    Route::prefix('auth')->group(function () {
        Route::get('/me',       [AuthController::class, 'me']);
        Route::post('/logout',  [AuthController::class, 'logout']);

        // Xác thực email
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
            ->middleware('throttle:6,1') // Giới hạn số lần bấm gửi lại (6 lần / 1 phút)
            ->name('verification.send');
    });

    Route::get('/profile',               [UserController::class, 'profile']);
    Route::put('/profile',               [UserController::class, 'updateProfile']);
    Route::post('/profile/avatar',       [UserController::class, 'updateAvatar']);
    Route::put('/profile/password',      [UserController::class, 'changePassword']);
    Route::get('/profile/point-history', [UserController::class, 'pointHistory']);


    // ── 2.2. Chức năng Khách hàng (Cart, Booking, Orders, Payment) ───────────
    Route::prefix('cart')->group(function () {
        Route::get('/',          [CartController::class, 'index']);   // Xem giỏ hàng
        Route::post('/',         [CartController::class, 'store']);   // Thêm món
        Route::patch('/{cart}',  [CartController::class, 'update']);  // Sửa số lượng
        Route::delete('/{cart}', [CartController::class, 'destroy']); // Xóa 1 món
        Route::delete('/',       [CartController::class, 'clear']);   // Xóa toàn bộ
    });

    Route::prefix('reservations')->group(function () {
        Route::post('/',                      [ReservationController::class, 'store']);
        Route::get('/my',                     [ReservationController::class, 'myReservations']);
        Route::patch('/{reservation}/cancel', [ReservationController::class, 'cancel']);
    });

    Route::post('/checkout', [CheckoutController::class, 'placeOrder']);
    Route::get('/orders',    [OrderController::class, 'index']);

    Route::post('/payment/vnpay',       [PaymentController::class, 'createPayment']);
    Route::get('/payment/vnpay-return', [PaymentController::class, 'vnpayReturn']);
    Route::get('/payment/vnpay-ipn',    [PaymentController::class, 'vnpayIPN']);


    // ── 2.3. Nhân viên Phục vụ (Waiter) ──────────────────────────────────────
    Route::prefix('waiter')->group(function () {
        // Quản lý Bàn
        Route::get('/tables',                  [TableController::class, 'index']);
        Route::get('/tables/{id}',             [TableController::class, 'show']);
        Route::post('/tables/open',            [OrderController::class, 'openTable']);
        Route::post('/tables/cancel',          [OrderController::class, 'cancelTable']);

        // Quản lý Order
        Route::post('/orders',                 [OrderController::class, 'store']);
        Route::get('/orders/today',            [OrderController::class, 'getTodayOrders']);
        Route::get('/orders/active/{tableId}', [OrderController::class, 'getActiveOrder']);
        Route::put('/order-items/{id}/serve',  [OrderController::class, 'markItemAsServed']);
        Route::post('/checkout',               [CheckoutController::class, 'checkoutTable']);

        // Thông báo món sẵn sàng
        Route::get('/items/ready',               [OrderController::class, 'getReadyItems']);
        Route::get('/notifications/ready-count', [OrderController::class, 'getReadyCount']);

        // Khách hàng
        Route::get('/customers/search',        [UserController::class, 'searchByPhone']);
    });


    // ── 2.4. Nhân viên Bếp (Kitchen) ─────────────────────────────────────────
    Route::prefix('kitchen')->group(function () {
        Route::get('/pending',                [KitchenController::class, 'getPendingItems']);
        Route::get('/history',                [KitchenController::class, 'getHistoryItems']);
        Route::get('/menu-items',               [MenuController::class, 'getMenuItems']);
        Route::get('/ingredients',            [KitchenController::class, 'getAllIngredients']);
        Route::get('/ingredients/low-stock',  [IngredientController::class, 'getLowStock']);
        Route::apiResource('/ingredients', IngredientController::class)->names([
            'index'   => 'kitchen.ingredients.index',
            'store'   => 'kitchen.ingredients.store',
            'show'    => 'kitchen.ingredients.show',
            'update'  => 'kitchen.ingredients.update',
            'destroy' => 'kitchen.ingredients.destroy',
        ]);
        Route::patch('/items/{id}/status',    [KitchenController::class, 'updateItemStatus']);
        Route::get('/items/{id}/ingredients', [KitchenController::class, 'getItemIngredients']);
    });



    // ── 2.5. Admin ───────────────────────────────────────
    Route::prefix('admin')->group(function () {

        // Báo cáo
        Route::get('/reports/revenue',     [ReportController::class, 'getRevenue'])->middleware('permission:reports.view');
        Route::get('/reports/inventory',   [ReportController::class, 'getInventoryReport'])->middleware('permission:reports.view');
        Route::get('/reports/reservation', [ReportController::class, 'getReservationReport'])->middleware('permission:reports.view');
        Route::get('/reports/menu',        [ReportController::class, 'getMenuReport'])->middleware('permission:reports.view');
        Route::get('/reports/daily',       [ReportController::class, 'getDailySummary'])->middleware('permission:reports.view');
        Route::get('/reports/quick-stats', [ReportController::class, 'getQuickStats'])->middleware('permission:reports.view');

        // Đặt bàn
        Route::get('/reservations',                        [ReservationController::class, 'adminIndex'])->middleware('permission:reservations.manage');
        Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus'])->middleware('permission:reservations.manage');

        // Đơn hàng
        Route::get('/orders',                       [OrderController::class, 'adminIndex'])->middleware('permission:orders.manage,orders.create,orders.edit,orders.delete,dishes.manage');
        Route::post('/orders',                      [OrderController::class, 'adminStore'])->middleware('permission:orders.create');
        Route::patch('/orders/{id}/status',         [OrderController::class, 'updateStatus'])->middleware('permission:orders.edit,dishes.manage');
        Route::patch('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus'])->middleware('permission:orders.edit');
        Route::delete('/orders/{id}',               [OrderController::class, 'destroy'])->middleware('permission:orders.delete');

        // Menu
        Route::get('/menu-items',               [MenuController::class, 'getMenuItems'])->middleware('permission:menu.manage,menu.create,menu.edit,menu.delete');
        Route::post('/menu-items',              [MenuController::class, 'store'])->middleware('permission:menu.create');
        Route::put('/menu-items/{id}',          [MenuController::class, 'update'])->middleware('permission:menu.edit');
        Route::delete('/menu-items/{id}',       [MenuController::class, 'destroy'])->middleware('permission:menu.delete');
        Route::patch('/menu-items/{id}/status', [MenuController::class, 'updateStatus'])->middleware('permission:menu.edit');
        Route::patch('/menu/{id}/highlights',   [MenuController::class, 'toggleHighlights'])->middleware('permission:menu.edit');

        // Người dùng
        Route::get('/users',         [UserController::class, 'index'])->middleware('permission:users.manage,users.create,users.edit,users.delete');
        Route::post('/users',        [UserController::class, 'store'])->middleware('permission:users.create');
        Route::put('/users/{id}',    [UserController::class, 'update'])->middleware('permission:users.edit');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->middleware('permission:users.delete');

        // Nguyên liệu / Kho
        Route::get('/ingredients/low-stock', [IngredientController::class, 'getLowStock'])->middleware('permission:ingredients.manage,inventory.view,warehouse.operations,warehouse.transaction,warehouse.reports,warehouse-report.create,warehouse-report.save');
        Route::get('/ingredients', [IngredientController::class, 'index'])
            ->name('admin.ingredients.index')
            ->middleware('permission:ingredients.manage,inventory.view,warehouse.operations,warehouse.transaction,warehouse.reports,warehouse-report.create,warehouse-report.save');
        Route::post('/ingredients', [IngredientController::class, 'store'])
            ->name('admin.ingredients.store')
            ->middleware('permission:ingredients.create');
        Route::get('/ingredients/{ingredient}', [IngredientController::class, 'show'])
            ->name('admin.ingredients.show')
            ->middleware('permission:ingredients.manage,inventory.view,warehouse.operations,warehouse.transaction,warehouse.reports,warehouse-report.create,warehouse-report.save');
        Route::put('/ingredients/{ingredient}', [IngredientController::class, 'update'])
            ->name('admin.ingredients.update')
            ->middleware('permission:ingredients.edit');
        Route::patch('/ingredients/{ingredient}', [IngredientController::class, 'update'])
            ->middleware('permission:ingredients.edit');
        Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy'])
            ->name('admin.ingredients.destroy')
            ->middleware('permission:ingredients.delete');
        Route::post('/ingredients/{id}/transaction', [IngredientController::class, 'handleTransaction'])->middleware('permission:warehouse.transaction');
        Route::get('/ingredients/{id}/transactions', [IngredientController::class, 'getTransactions'])->middleware('permission:warehouse.operations,warehouse.transaction');

        // Báo cáo Nhập/Xuất Kho
        Route::post('/warehouse-reports', [WarehouseReportController::class, 'store'])->middleware('permission:warehouse-report.create,warehouse-report.save');
        Route::get('/warehouse-reports', [WarehouseReportController::class, 'index'])->middleware('permission:warehouse.reports,warehouse-report.create,warehouse-report.save');
        Route::get('/warehouse-reports/latest', [WarehouseReportController::class, 'getLatest'])->middleware('permission:warehouse.reports,warehouse-report.create,warehouse-report.save');
        Route::get('/warehouse-reports/{id}', [WarehouseReportController::class, 'show'])->middleware('permission:warehouse.reports,warehouse-report.create,warehouse-report.save');
        Route::put('/warehouse-reports/{id}', [WarehouseReportController::class, 'update'])->middleware('permission:warehouse-report.save');
        Route::delete('/warehouse-reports/{id}', [WarehouseReportController::class, 'destroy'])->middleware('permission:warehouse-report.save');

        // Quản lý Quyền (Permissions)
        Route::get('/permissions', [PermissionController::class, 'getAllPermissions'])->middleware('permission:permissions.manage,permissions.grant,permissions.revoke');
        Route::get('/permissions/users', [PermissionController::class, 'getUsersWithPermissions'])->middleware('permission:permissions.manage,permissions.grant,permissions.revoke');
        Route::get('/permissions/users/{userId}', [PermissionController::class, 'getUserPermissions'])->middleware('permission:permissions.manage,permissions.grant,permissions.revoke');
        Route::post('/permissions/users/{userId}/grant', [PermissionController::class, 'grantPermission'])->middleware('permission:permissions.grant');
        Route::delete('/permissions/users/{userId}/revoke/{permissionId}', [PermissionController::class, 'revokePermission'])->middleware('permission:permissions.revoke');
        Route::get('/permissions/audit-log', [PermissionController::class, 'getAuditLog'])->middleware('permission:permissions.manage,permissions.grant,permissions.revoke');
        Route::get('/permissions/audit-log/users/{userId}', [PermissionController::class, 'getUserAuditLog'])->middleware('permission:permissions.manage,permissions.grant,permissions.revoke');
        Route::get('/permissions/check/{userId}/{permissionSlug}', [PermissionController::class, 'checkPermission'])->middleware('permission:permissions.manage,permissions.grant,permissions.revoke');

        // Liên hệ
        Route::get('/contacts',               [ContactController::class, 'indexAdmin'])->middleware('permission:contacts.manage');
        Route::patch('/contacts/{id}/status', [ContactController::class, 'updateStatus'])->middleware('permission:contacts.manage');
        Route::delete('/contacts/{id}',       [ContactController::class, 'destroy'])->middleware('permission:contacts.manage');

    }); 

    Route::prefix('cashier')->group(function () {
            // Quản lý Đặt bàn
            Route::get('/reservations',                       [ReservationController::class, 'adminIndex']);
            Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);

            // Quản lý Đơn hàng
            Route::get('/orders',                       [OrderController::class, 'adminIndex']);
            Route::post('/orders',                      [OrderController::class, 'adminStore']);
            Route::patch('/orders/{id}/status',         [OrderController::class, 'updateStatus']);
            Route::patch('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus']);
            Route::delete('/orders/{id}',               [OrderController::class, 'destroy']);
    });
});
