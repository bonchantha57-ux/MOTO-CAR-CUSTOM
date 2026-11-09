<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Authentication Endpoints
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/b2b/register', [AuthController::class, 'b2bRegister']);

    // Protected Auth routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/switch-mode', [AuthController::class, 'switchMode']);
    });
});

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\OrderController;

Route::prefix('v1')->group(function () {
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/brands', [ProductController::class, 'brands']);
    Route::post('/upload', [ProductController::class, 'uploadImage']);
    Route::post('/admin/products', [ProductController::class, 'store']);
    Route::get('/admin/dashboard-stats', [ProductController::class, 'dashboardStats']);

    // Order Creation, Tracking & Invoices
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/checkout', [OrderController::class, 'store']);
    Route::get('/orders/track/{orderNumber}', [OrderController::class, 'track']);
    Route::get('/orders/{orderNumber}/invoice', [OrderController::class, 'invoice']);
});

// Wishlist Operations
Route::prefix('v1/wishlist')->group(function () {
    Route::get('/', [AccountController::class, 'getWishlist']);
    Route::post('/toggle/{productId}', [AccountController::class, 'toggleWishlist']);
    Route::delete('/{productId}', [AccountController::class, 'removeWishlist']);
});

// Account & Customer Operations (Real DB Orders, Garage Vehicles, Metrics)
Route::prefix('v1/account')->group(function () {
    Route::get('/orders', [AccountController::class, 'orders']);
    Route::get('/vehicles', [AccountController::class, 'vehicles']);
    Route::post('/vehicles', [AccountController::class, 'storeVehicle']);
    Route::delete('/vehicles/{id}', [AccountController::class, 'destroyVehicle']);
    Route::put('/vehicles/{id}/primary', [AccountController::class, 'setPrimaryVehicle']);
    Route::get('/dashboard-summary', [AccountController::class, 'dashboardSummary']);
});

// Admin Operations (Users, Products, Orders, Garages)
Route::prefix('v1/admin')->group(function () {
    // User Management & Password Reset
    Route::get('/users', [AdminController::class, 'users']);
    Route::post('/users', [AdminController::class, 'storeUser']);
    Route::put('/users/{id}', [AdminController::class, 'updateUser']);
    Route::put('/users/{id}/password', [AdminController::class, 'resetPassword']);
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser']);

    // Product Edit & Delete
    Route::get('/products/{id}', [AdminController::class, 'getProduct']);
    Route::put('/products/{id}', [AdminController::class, 'updateProduct']);
    Route::delete('/products/{id}', [AdminController::class, 'destroyProduct']);

    // Garages & Partners
    Route::get('/garages', [AdminController::class, 'garages']);
    Route::put('/garages/{id}/status', [AdminController::class, 'updateGarageStatus']);

    // Orders Management
    Route::get('/orders', [AdminController::class, 'orders']);
    Route::put('/orders/{id}/status', [AdminController::class, 'updateOrderStatus']);
    Route::delete('/orders/{id}', [AdminController::class, 'destroyOrder']);
});


// Legacy sanctum test route
Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'me']);
