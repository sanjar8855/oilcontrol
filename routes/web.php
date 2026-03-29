<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceLogController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\VehicleController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Telegram Bot Webhook (Auth siz - Telegram serveri chaqiradi)
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])->name('telegram.webhook');

// Telegram Bot boshqaruvi (Admin uchun)
Route::middleware('auth')->prefix('telegram')->group(function () {
    Route::get('/set-webhook', [TelegramWebhookController::class, 'setWebhook'])->name('telegram.set-webhook');
    Route::get('/webhook-info', [TelegramWebhookController::class, 'getWebhookInfo'])->name('telegram.webhook-info');
    Route::get('/delete-webhook', [TelegramWebhookController::class, 'deleteWebhook'])->name('telegram.delete-webhook');
    Route::get('/bot-info', [TelegramWebhookController::class, 'getBotInfo'])->name('telegram.bot-info');
});

Route::middleware('auth')->group(function () {
    // Mijozlar (Clients) CRUD
    Route::post('/clients/store-with-vehicle', [ClientController::class, 'storeWithVehicle'])->name('clients.store-with-vehicle');
    Route::resource('clients', ClientController::class);

    // Avtomobillar (Vehicles) CRUD
    Route::get('/vehicles/search', [VehicleController::class, 'search'])->name('vehicles.search');
    Route::resource('vehicles', VehicleController::class);

    // Servis Yozuvlari (Service Logs) CRUD
    Route::resource('service-logs', ServiceLogController::class);

    // Kategoriyalar (Categories) CRUD
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Mahsulotlar (Products) CRUD
    Route::resource('products', ProductController::class);
    Route::get('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
    Route::post('/products/{product}/adjust-stock', [ProductController::class, 'processStockAdjustment'])->name('products.process-stock-adjustment');

    // Xarajatlar (Expenses) CRUD
    Route::resource('expenses', ExpenseController::class)->except(['show']);

    // Profil boshqaruvi
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
