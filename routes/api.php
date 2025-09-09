<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FooterController;
use App\Http\Controllers\SliderController;
use App\Http\Controllers\AboutUsController;
use App\Http\Controllers\FeatureController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ContactUsController;

Route::get('sliders', [SliderController::class, 'index']);
Route::get('features', [FeatureController::class, 'index']);
Route::get('about-us', [AboutUsController::class, 'index']);
Route::post('contact-us', [ContactUsController::class, 'store']);
Route::get('footer', [FooterController::class, 'index']);
Route::get('categories', [CategoryController::class, 'index']);

Route::prefix('products')
    ->controller(ProductController::class)
    ->group(function () {
    Route::get('/', 'index');
    Route::get('random', 'random');
    Route::get('tab', 'tab');
    Route::get('{product:slug}', 'show');
});

Route::get('menu', [ProductController::class, 'menu']);

Route::controller(AuthController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('logout', 'logout')->middleware('auth:sanctum');
    Route::post('forget-password', 'forgetPassword');
    Route::post('reset-password', 'resetPassword');
});

Route::prefix('profile')
    ->controller(ProfileController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
    Route::get('provinces-cities', 'provincesCities');
    Route::get('addresses', 'indexAddress');
    Route::post('addresses', 'storeAddress');
    Route::put('addresses/{user_address}', 'updateAddress');
    Route::get('addresses/{user_address}', 'showAddress');
    Route::delete('addresses/{user_address}', 'destroyAddress');
    Route::get('add-to-wishlist', 'addToWishlist');
    Route::post('remove-from-wishlist/{wishlist}', 'removeFromWishlist');
    Route::get('wishlist', 'wishlist');
    Route::get('user', 'getUser');
    Route::put('user', 'UpdateUser');
    Route::get('orders', 'orders');
    Route::get('transactions', 'transactions');
});

Route::controller(PaymentController::class)
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::post('check-coupon', 'checkCoupon');
        Route::post('payment/send', 'send');
        Route::post('payment/verify', 'verify');
});
