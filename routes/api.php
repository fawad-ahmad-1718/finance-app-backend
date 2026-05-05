<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/logout',   [AuthController::class, 'logout']);

// Protected routes (session auth)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me',  [AuthController::class, 'me']);
    Route::put('/me',  [AuthController::class, 'update']);

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Transactions
    Route::get('/transactions/summary', [TransactionController::class, 'summary']);
    Route::apiResource('transactions', TransactionController::class);
});