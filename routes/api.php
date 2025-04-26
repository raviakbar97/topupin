<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the App\Providers\RouteServiceProvider within a group
| which is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth Routes
Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->middleware('auth:sanctum');

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    // Games & Products
    Route::get('/games', [\App\Http\Controllers\Api\Ditusi\ProductController::class, 'games']);
    Route::get('/products', [\App\Http\Controllers\Api\Ditusi\ProductController::class, 'products']);
    
    // Transactions
    Route::post('/transactions', [\App\Http\Controllers\Api\Ditusi\TransactionController::class, 'create']);
    Route::get('/transactions/{id}', [\App\Http\Controllers\Api\Ditusi\TransactionController::class, 'status']);
    
    // Balance
    Route::get('/balance', [\App\Http\Controllers\Api\Ditusi\BalanceController::class, 'check']);
}); 