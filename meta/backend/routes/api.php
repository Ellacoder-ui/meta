<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:3,1');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Inventory views (all authenticated users)
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{category}', [CategoryController::class, 'show']);
        Route::get('/items', [ItemController::class, 'index']);
        Route::get('/items/{item}', [ItemController::class, 'show']);

        // Transactions
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::post('/transactions/borrow', [TransactionController::class, 'borrow']);
        Route::patch('/transactions/{transaction}/status', [TransactionController::class, 'updateStatus'])
            ->middleware('admin');

        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        // Admin-only mutations
        Route::middleware('admin')->group(function () {
            Route::apiResource('categories', CategoryController::class)
                ->except(['index', 'show']);
            Route::apiResource('items', ItemController::class)
                ->except(['index', 'show']);
            Route::get('/users', [UserController::class, 'index']);
            Route::patch('/users/{user}/role', [UserController::class, 'updateRole']);
        });
    });
});
