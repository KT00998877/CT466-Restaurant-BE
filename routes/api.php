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

// ── Auth ────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::post('/contacts', [ContactController::class, 'store']);
Route::get('/contacts', [ContactController::class, 'index']);

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    
});

// ── Menu ────────────────────────────────────────────────
Route::get('/menu',       [MenuController::class, 'index']);
Route::get('/menu-items', [MenuController::class, 'getMenuItems']);

// ── Đặt bàn ─────────────────────────────────────────────
// Public: xem danh sách bàn (truyền ?reserved_at=... để lọc)
Route::get('/tables', [ReservationController::class, 'getTables']);
Route::get('/tables/check-availability', [ReservationController::class, 'checkAvailability']);

// Cần đăng nhập
Route::middleware('auth:sanctum')->prefix('reservations')->group(function () {
    Route::post('/',               [ReservationController::class, 'store']);
    Route::get('/my',              [ReservationController::class, 'myReservations']);
    Route::patch('/{reservation}/cancel', [ReservationController::class, 'cancel']);
});

Route::middleware('auth:sanctum')->prefix('waiter')->group(function () {
    Route::post('/tables/open', [OrderController::class, 'openTable']);
    Route::get('/tables', [TableController::class, 'index']);
    Route::get('/tables/{id}', [TableController::class, 'show']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/active/{tableId}', [OrderController::class, 'getActiveOrder']);
    Route::post('/checkout', [CheckoutController::class, 'checkoutTable']);
    Route::put('/order-items/{id}/serve', [OrderController::class, 'markItemAsServed']);
    Route::post('/tables/cancel', [OrderController::class, 'cancelTable']);
    Route::get('/notifications/ready-count', [OrderController::class, 'getReadyCount']);
    Route::get('/items/ready', [OrderController::class, 'getReadyItems']);
    Route::get('/orders/today', [OrderController::class, 'getTodayOrders']);
});


// ── Bếp ────────────────────────────────────────────────
Route::middleware('auth:sanctum')->prefix('kitchen')->group(function () {
    Route::get('/pending', [KitchenController::class, 'getPendingItems']);
    Route::get('/history', [KitchenController::class, 'getHistoryItems']);
    Route::patch('/items/{id}/status', [KitchenController::class, 'updateItemStatus']);
});


// Admin
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {

    Route::get('/reservations',                        [ReservationController::class, 'adminIndex']);
    Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);
    Route::get('/menu-items', [MenuController::class, 'getMenuItems']);
    Route::patch('/menu-items/{id}/status', [MenuController::class, 'updateStatus']);
    Route::put('/menu-items/{id}', [MenuController::class, 'update']);
    Route::delete('/menu-items/{id}', [MenuController::class, 'destroy']);
    Route::post('/menu-items', [MenuController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'adminIndex']);
    Route::post('/orders', [OrderController::class, 'adminStore']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
    Route::get('/reservations', [ReservationController::class, 'adminIndex']);
    Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus']);
    Route::get('/users', [UserController::class, 'index']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::post('/users', [UserController::class, 'store']);

    Route::get('/ingredients/low-stock', [IngredientController::class, 'getLowStock']);
    Route::apiResource('/ingredients', IngredientController::class);
    // Chỉnh sửa số lượng (nhập/xuất)
    Route::post('/ingredients/{id}/transaction', [IngredientController::class, 'handleTransaction']);

    // Xem lịch sử nhập/xuất của nguyên liệu
    Route::get('/ingredients/{id}/transactions', [IngredientController::class, 'getTransactions']);
    Route::get('/reports/revenue', [ReportController::class, 'getRevenue']);
});

Route::middleware('auth:sanctum')->prefix('cart')->group(function () {
    Route::get('/',          [CartController::class, 'index']);   // Xem giỏ hàng
    Route::post('/',         [CartController::class, 'store']);   // Thêm món
    Route::patch('/{cart}',  [CartController::class, 'update']);  // Sửa số lượng
    Route::delete('/{cart}', [CartController::class, 'destroy']); // Xóa 1 món
    Route::delete('/',       [CartController::class, 'clear']);   // Xóa toàn bộ

});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/checkout', [CheckoutController::class, 'placeOrder']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/payment/vnpay', [PaymentController::class, 'createPayment']);
    Route::get('/payment/vnpay-return', [PaymentController::class, 'vnpayReturn']);
    Route::get('/payment/vnpay-ipn', [PaymentController::class, 'vnpayIPN']);
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('profile/avatar',    [UserController::class, 'updateAvatar']);    
    Route::put('profile/password',  [UserController::class, 'changePassword']); 
});

Route::post('/chatbot', [ChatbotController::class, 'handleChat']);