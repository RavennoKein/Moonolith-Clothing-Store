<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Admin\CartController;
use App\Http\Controllers\Api\Admin\ItemController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\User\ReviewController;
use App\Http\Controllers\Api\User\ProfileController;
use App\Http\Controllers\Api\User\CheckoutController;
use App\Http\Controllers\Api\User\FavoriteController;
use App\Http\Controllers\Api\Admin\CashflowController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Public\MostBuyController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\FlashSaleController;
use App\Http\Controllers\Api\MidtransWebhookController;
use App\Http\Controllers\Api\Admin\ItemVariantController;
use App\Http\Controllers\Api\Public\PublicItemController;
use App\Http\Controllers\Api\Public\PublicFlashSaleController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['api', 'auth:sanctum', 'role:admin,super'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/admins', [AdminController::class, 'index']);
    Route::put('/admins/{user}', [AdminController::class, 'update']);
    Route::middleware('role:super')->group(function () {
        Route::post('/admins', [AdminController::class, 'store']);
        Route::delete('/admins/{user}', [AdminController::class, 'destroy']);
    });
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    Route::get('/items', [ItemController::class, 'index']);
    Route::get('/items/{id}', [ItemController::class, 'show']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::put('/items/{item}', [ItemController::class, 'update']);
    Route::delete('/items/{item}', [ItemController::class, 'destroy']);
    Route::post('/items/{item}/variants/restock', [ItemVariantController::class, 'restock']);
    Route::get('/flash-sales', [FlashSaleController::class, 'index']);
    Route::post('/flash-sales', [FlashSaleController::class, 'store']);
    Route::get('/flash-sales/available-items', [FlashSaleController::class, 'availableItems']);
    Route::get('/flash-sales/{id}', [FlashSaleController::class, 'show']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::get('/cashflow', [CashflowController::class, 'cashflow']);
    Route::get('/orders/report', [OrderController::class, 'exportReport']);
});

Route::middleware(['api', 'auth:sanctum', 'role:user'])->prefix('user')->group(function () {
    Route::get('/profile', [ProfileController::class, 'index']);
    Route::put('/profile/update', [ProfileController::class, 'update']);
    Route::post('/favorites/toggle', [FavoriteController::class, 'toggle']);
    Route::get('/favorites/list', [FavoriteController::class, 'index']);
    Route::get('/favorites/check/{id}', [FavoriteController::class, 'checkStatus']);
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'show']);
        Route::post('/add', [CartController::class, 'add']);
        Route::put('/item/{id}', [CartController::class, 'updateItem']);
        Route::delete('/item/{id}', [CartController::class, 'removeItem']);
        Route::post('/validate', [CartController::class, 'validateCart']);
    });
    Route::post('/check-shipping', [CheckoutController::class, 'checkShipping']);
    Route::post('/checkout', [CheckoutController::class, 'checkout']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{invoice}', [OrderController::class, 'showByInvoice']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::get('/orders/{invoice}/pdf', [OrderController::class, 'downloadPdf']);
    Route::post('/reviews', [ReviewController::class, 'store']);
});

Route::prefix('public')->group(function () {
    Route::get('/flash-sale', [PublicFlashSaleController::class, 'active']);
    Route::get('/flash-sale/{id}', [PublicFlashSaleController::class, 'show']);
    Route::get('/items', [PublicItemController::class, 'index']);
    Route::get('/items/{id}', [PublicItemController::class, 'show']);
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/most-buy', [MostBuyController::class, 'index']);
    Route::get('/items/{id}/rating', [PublicItemController::class, 'ratingSummary']);
    Route::get('/items/{id}/reviews', [PublicItemController::class, 'reviews']);
});

Route::post('/midtrans/webhook', [MidtransWebhookController::class, 'handle']);