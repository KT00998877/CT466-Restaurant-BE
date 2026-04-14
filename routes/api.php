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
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC ROUTES (Không yêu cầu đăng nhập)
|--------------------------------------------------------------------------
*/

// ── Auth ────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

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
        Route::get('/ingredients/low-stock',         [IngredientController::class, 'getLowStock']);
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


    // ── 2.5. Quản trị viên (Admin) ───────────────────────────────────────────
       
        Route::prefix('admin')->middleware(AdminMiddleware::class)->group(function () {

        // Dashboard / Báo cáo
        Route::get('/reports/revenue',   [ReportController::class, 'getRevenue']);
        Route::get('/reports/inventory', [ReportController::class, 'getInventoryReport']);

        // Quản lý Đặt bàn
        Route::get('/reservations',                       [ReservationController::class, 'adminIndex']);
        Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);

        // Quản lý Đơn hàng
        Route::get('/orders',                       [OrderController::class, 'adminIndex']);
        Route::post('/orders',                      [OrderController::class, 'adminStore']);
        Route::patch('/orders/{id}/status',         [OrderController::class, 'updateStatus']);
        Route::patch('/orders/{id}/payment-status', [OrderController::class, 'updatePaymentStatus']);
        Route::delete('/orders/{id}',               [OrderController::class, 'destroy']);

        // Quản lý Menu (Món ăn)
        Route::get('/menu-items',               [MenuController::class, 'getMenuItems']);
        Route::post('/menu-items',              [MenuController::class, 'store']);
        Route::put('/menu-items/{id}',          [MenuController::class, 'update']);
        Route::delete('/menu-items/{id}',       [MenuController::class, 'destroy']);
        Route::patch('/menu-items/{id}/status', [MenuController::class, 'updateStatus']);
        Route::patch('/menu/{id}/highlights',   [MenuController::class, 'toggleHighlights']);

        // Quản lý Người dùng
        Route::get('/users',         [UserController::class, 'index']);
        Route::post('/users',        [UserController::class, 'store']);
        Route::put('/users/{id}',    [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);

        // Quản lý Kho & Nguyên liệu
        Route::get('/ingredients/low-stock',         [IngredientController::class, 'getLowStock']);
        Route::apiResource('/ingredients', IngredientController::class)->names([
            'index'   => 'admin.ingredients.index',
            'store'   => 'admin.ingredients.store',
            'show'    => 'admin.ingredients.show',
            'update'  => 'admin.ingredients.update',
            'destroy' => 'admin.ingredients.destroy',
        ]);
        Route::post('/ingredients/{id}/transaction', [IngredientController::class, 'handleTransaction']); // Nhập/xuất
        Route::get('/ingredients/{id}/transactions', [IngredientController::class, 'getTransactions']);   // Lịch sử

        // Quản lý Liên hệ
        Route::get('/contacts',               [ContactController::class, 'indexAdmin']);
        Route::patch('/contacts/{id}/status', [ContactController::class, 'updateStatus']);
        Route::delete('/contacts/{id}',       [ContactController::class, 'destroy']);
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
