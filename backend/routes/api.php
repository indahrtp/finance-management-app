<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CoaController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

// Publik
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');

// Login
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('coa', CoaController::class);
    Route::apiResource('transactions', TransactionController::class);
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
    Route::get('/reports/profit-loss/export', [ReportController::class, 'exportProfitLoss']);
});