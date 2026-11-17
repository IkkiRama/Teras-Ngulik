<?php

use App\Http\Controllers\CommerceAdminController;
use App\Http\Controllers\CommerceController as C;
use Illuminate\Support\Facades\Route;

Route::get('/marketplace/{category?}', [C::class, 'marketplace']);
Route::post('/webhooks/midtrans', [C::class, 'webhook'])->middleware('throttle:120,1');
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/checkout', [C::class, 'start'])->middleware('throttle:10,1');
    Route::get('/checkout/{order?}', [C::class, 'checkout']);
    Route::post('/orders/{order}/cancel', [C::class, 'cancel']);
    Route::get('/dashboard/pesanan', [C::class, 'orders']);
    Route::get('/dashboard/reward', [C::class, 'rewards']);
    Route::post('/rewards/{id}/redeem', [C::class, 'redeem'])->middleware('throttle:10,1');
    Route::get('/admin/orders', [C::class, 'adminOrders']);
    Route::get('/admin/settings', [C::class, 'adminOrders']);
    Route::post('/admin/rewards/{id}/fulfill', [C::class, 'fulfill']);
    Route::get('/admin/catalog', [CommerceAdminController::class, 'catalog']);
    Route::post('/admin/products', [CommerceAdminController::class, 'product']);
    Route::put('/admin/products/{product}', [CommerceAdminController::class, 'product']);
    Route::post('/admin/coupons', [CommerceAdminController::class, 'coupon']);
    Route::put('/orders/{order}/delivery', [CommerceAdminController::class, 'delivery']);
    Route::post('/orders/{order}/refund', [CommerceAdminController::class, 'refundRequest']);
    Route::post('/admin/orders/{order}/fulfill', [CommerceAdminController::class, 'fulfill']);
    Route::post('/admin/refunds/{id}/review', [CommerceAdminController::class, 'refundReview']);
});
