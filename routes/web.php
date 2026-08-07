<?php

use App\Http\Controllers\CarMakeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ServiceLogController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WorkshopSwitchController;
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
    // Superadmin uchun workshop tanlash (nomidan ishlash)
    Route::get('/workshops/switch', [WorkshopSwitchController::class, 'index'])->name('workshops.switch.index');
    Route::post('/workshops/switch/{workshop}', [WorkshopSwitchController::class, 'switch'])->name('workshops.switch');
    Route::post('/workshops/switch-exit', [WorkshopSwitchController::class, 'exit'])->name('workshops.switch.exit');

    // Foydalanuvchilar (Users) CRUD - Faqat superadmin va director
    Route::resource('users', UserController::class)->middleware('can:users.manage');

    // Avtomobil markalari va turlari CRUD - Faqat superadmin va director
    Route::middleware('can:car-makes.manage')->group(function () {
        Route::post('/car-makes/models', [CarMakeController::class, 'storeModel'])->name('car-makes.models.store');
        Route::put('/car-makes/models/{carModel}', [CarMakeController::class, 'updateModel'])->name('car-makes.models.update');
        Route::delete('/car-makes/models/{carModel}', [CarMakeController::class, 'destroyModel'])->name('car-makes.models.destroy');
        Route::post('/car-makes/models/{carModel}/products', [CarMakeController::class, 'attachProduct'])->name('car-makes.models.products.attach');
        Route::put('/car-makes/models/{carModel}/products/{product}', [CarMakeController::class, 'updateProductQuantity'])->name('car-makes.models.products.update');
        Route::delete('/car-makes/models/{carModel}/products/{product}', [CarMakeController::class, 'detachProduct'])->name('car-makes.models.products.detach');
        Route::resource('car-makes', CarMakeController::class)->except(['show', 'create', 'edit']);
    });

    // Mijozlar (Clients) CRUD - savdo jarayoni, xodim ham kira oladi
    Route::middleware('can:clients.manage')->group(function () {
        Route::post('/clients/store-with-vehicle', [ClientController::class, 'storeWithVehicle'])->name('clients.store-with-vehicle');
        Route::resource('clients', ClientController::class);
    });

    // Avtomobillar (Vehicles) CRUD - savdo jarayoni, xodim ham kira oladi
    Route::middleware('can:vehicles.manage')->group(function () {
        Route::get('/vehicles/search', [VehicleController::class, 'search'])->name('vehicles.search');
        Route::get('/vehicles/{vehicle}/car-model-info', [VehicleController::class, 'carModelInfo'])->name('vehicles.car-model-info');
        Route::resource('vehicles', VehicleController::class);
    });

    // Servis Yozuvlari (Service Logs) CRUD - savdo jarayoni, xodim ham kira oladi
    Route::resource('service-logs', ServiceLogController::class)->middleware('can:service-logs.manage');

    // Kategoriyalar (Categories) CRUD - faqat superadmin/director/menejer
    Route::resource('categories', CategoryController::class)->middleware('can:categories.manage');

    // Mahsulotlar (Products) CRUD - faqat superadmin/director/menejer
    Route::middleware('can:products.manage')->group(function () {
        Route::resource('products', ProductController::class);
        Route::get('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
        Route::post('/products/{product}/adjust-stock', [ProductController::class, 'processStockAdjustment'])->name('products.process-stock-adjustment');

        // Inventarizatsiya (ombor sanog'i) - faqat superadmin/director/menejer
        Route::post('/inventories/{inventory}/complete', [InventoryController::class, 'complete'])->name('inventories.complete');
        Route::resource('inventories', InventoryController::class)->except(['edit']);
    });

    // Xarajatlar (Expenses) CRUD - faqat superadmin/director/menejer
    Route::resource('expenses', ExpenseController::class)->except(['show'])->middleware('can:expenses.manage');

    // To'lovlar (Payments) CRUD - savdo jarayoni, xodim ham kira oladi
    Route::resource('payments', PaymentController::class)->middleware('can:payments.manage');

    // Oylik maoshlar (Salaries) CRUD - faqat superadmin/director/menejer
    Route::middleware('can:salaries.manage')->group(function () {
        Route::get('/salaries/report', [SalaryController::class, 'report'])->name('salaries.report');
        Route::resource('salaries', SalaryController::class)->except(['edit', 'update']);
    });

    // Hisobotlar - faqat superadmin/director/menejer
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware('can:reports.view');

    // Profil boshqaruvi
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
