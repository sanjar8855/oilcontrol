<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\ServiceLogController;
use App\Http\Controllers\API\VehicleController;
use App\Http\Controllers\MiniApp\GarageController;
use App\Http\Middleware\VerifyTelegramInitData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Telegram Mini App (mijozga qaratilgan, initData orqali autentifikatsiya qilinadi)
Route::prefix('miniapp')->middleware(VerifyTelegramInitData::class)->group(function () {
    Route::get('/garage', [GarageController::class, 'index']);
    Route::get('/vehicles/{vehicle}', [GarageController::class, 'vehicle']);
});

// Public routes (No authentication required)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (Authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Clients API
    Route::group(['as' => 'api.'], function () {

        // Clients API
        Route::apiResource('clients', ClientController::class);

        // Vehicles API
        Route::apiResource('vehicles', VehicleController::class);

        // Service Logs API
        Route::apiResource('service-logs', ServiceLogController::class);

    }); // <-- Guruh shu yerda yopiladi
});
