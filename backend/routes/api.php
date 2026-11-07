<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BudgetController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CoaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Middleware\ExtendTokenExpiry;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', ExtendTokenExpiry::class])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/coa/next-code', [CoaController::class, 'suggestCode']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('coa', CoaController::class);
    Route::apiResource('wallets', WalletController::class);

    Route::post('/transactions/{transaction}/post', [TransactionController::class, 'markPosted']);
    Route::post('/transactions/{transaction}/void', [TransactionController::class, 'markVoid']);
    Route::post('/transactions/{transaction}/attachment', [TransactionController::class, 'uploadAttachment']);
    Route::get('/transactions/{transaction}/attachment', [TransactionController::class, 'downloadAttachment']);
    Route::delete('/transactions/{transaction}/attachment', [TransactionController::class, 'deleteAttachment']);
    Route::apiResource('transactions', TransactionController::class);

    Route::get('/budgets', [BudgetController::class, 'index']);
    Route::post('/budgets', [BudgetController::class, 'store']);
    Route::delete('/budgets/{budget}', [BudgetController::class, 'destroy']);

    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss']);
    Route::get('/reports/profit-loss/monthly', [ReportController::class, 'profitLossMonthly']);
    Route::get('/reports/profit-loss/export', [ReportController::class, 'exportProfitLoss']);
    Route::get('/period-range', [ReportController::class, 'periodRange']);

    Route::get('/dashboard', [DashboardController::class, 'index']);
});