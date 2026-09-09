<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\CarMakeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GlobalProductController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\ServiceLogController;
use App\Http\Controllers\SubscriptionPaymentController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WorkshopController;
use App\Http\Controllers\WorkshopSwitchController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$renderWelcome = function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
};

Route::get('/', $renderWelcome)->name('home');

// Rus tilidagi landing — docs: Bosqich 4 "Landing /ru + hreflang"
Route::get('/ru', function () use ($renderWelcome) {
    app()->setLocale('ru');
    return $renderWelcome();
})->name('home.ru');

// Telegram Mini App — mijoz Telegram ichida ochadigan, alohida (Inertia'siz) SPA qobig'i
Route::get('/miniapp', fn () => view('miniapp'))->name('miniapp');

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
    Route::get('/set-menu-button', [TelegramWebhookController::class, 'setMenuButton'])->name('telegram.set-menu-button');
});

Route::middleware('auth')->group(function () {
    // Yangi workshop uchun majburiy o'qitish sikli
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/products', [OnboardingController::class, 'products'])->name('products');
        Route::post('/products', [OnboardingController::class, 'storeProducts'])->name('products.store');
        Route::get('/vehicle', [OnboardingController::class, 'vehicle'])->name('vehicle');
        Route::post('/vehicle', [OnboardingController::class, 'storeVehicle'])->name('vehicle.store');
        Route::post('/skip', [OnboardingController::class, 'skip'])->name('skip');
    });

    // Superadmin uchun workshop tanlash (nomidan ishlash)
    Route::get('/workshops/switch', [WorkshopSwitchController::class, 'index'])->name('workshops.switch.index');
    Route::post('/workshops/switch/{workshop}', [WorkshopSwitchController::class, 'switch'])->name('workshops.switch');
    Route::post('/workshops/switch-exit', [WorkshopSwitchController::class, 'exit'])->name('workshops.switch.exit');

    // Kompaniyalar (Workshops) CRUD - Faqat superadmin
    Route::resource('workshops', WorkshopController::class)->middleware('can:workshops.manage');

    // Obuna to'lovlari - Faqat superadmin
    Route::post('/workshops/{workshop}/subscription-payments', [SubscriptionPaymentController::class, 'store'])
        ->name('subscription-payments.store')
        ->middleware('can:subscription-payments.manage');

    // Global mahsulotlar katalogi (barcha kompaniyalar uchun umumiy namuna) - Faqat superadmin
    Route::middleware('can:global-products.manage')->group(function () {
        Route::get('/global-products/bulk-create', [GlobalProductController::class, 'bulkCreate'])->name('global-products.bulk-create');
        Route::post('/global-products/bulk-store', [GlobalProductController::class, 'bulkStore'])->name('global-products.bulk-store');
        Route::post('/global-products/{globalProduct}/car-models', [GlobalProductController::class, 'attachCarModel'])->name('global-products.car-models.attach');
        Route::put('/global-products/{globalProduct}/car-models/{carModel}', [GlobalProductController::class, 'updateCarModelQuantity'])->name('global-products.car-models.update');
        Route::delete('/global-products/{globalProduct}/car-models/{carModel}', [GlobalProductController::class, 'detachCarModel'])->name('global-products.car-models.destroy');
        Route::resource('global-products', GlobalProductController::class)->except(['show']);
    });

    // Foydalanuvchilar (Users) CRUD - Faqat superadmin va director
    Route::resource('users', UserController::class)->middleware('can:users.manage');

    // Filiallar (Branches) CRUD - Faqat superadmin va director
    Route::resource('branches', BranchController::class)->except(['show'])->middleware('can:branches.manage');

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
        Route::post('/clients/{client}/telegram-unlink', [ClientController::class, 'telegramUnlink'])->name('clients.telegram-unlink');
        Route::post('/clients/{client}/telegram-regenerate-link', [ClientController::class, 'telegramRegenerateLink'])->name('clients.telegram-regenerate-link');
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
    Route::middleware('can:categories.manage')->group(function () {
        Route::get('/categories/export/excel', [CategoryController::class, 'exportExcel'])->name('categories.export.excel');
        Route::get('/categories/export/pdf', [CategoryController::class, 'exportPdf'])->name('categories.export.pdf');
        Route::resource('categories', CategoryController::class);
    });

    // Ta'minotchilar (Suppliers) CRUD - faqat superadmin/director/menejer
    Route::middleware('can:suppliers.manage')->group(function () {
        Route::post('/suppliers/{supplier}/payments', [SupplierController::class, 'storePayment'])->name('suppliers.payments.store');
        Route::resource('suppliers', SupplierController::class);
    });

    // Mahsulotlar (Products) CRUD - faqat superadmin/director/menejer
    Route::middleware('can:products.manage')->group(function () {
        Route::get('/products/export/excel', [ProductController::class, 'exportExcel'])->name('products.export.excel');
        Route::get('/products/export/pdf', [ProductController::class, 'exportPdf'])->name('products.export.pdf');
        Route::get('/products/bulk-create', [ProductController::class, 'bulkCreate'])->name('products.bulk-create');
        Route::post('/products/bulk-store', [ProductController::class, 'bulkStore'])->name('products.bulk-store');
        Route::get('/products/catalog', [ProductController::class, 'catalog'])->name('products.catalog');
        Route::post('/products/copy-from-catalog', [ProductController::class, 'copyFromCatalog'])->name('products.copy-from-catalog');
        Route::resource('products', ProductController::class);
        Route::get('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
        Route::post('/products/{product}/adjust-stock', [ProductController::class, 'processStockAdjustment'])->name('products.process-stock-adjustment');

        // Inventarizatsiya (ombor sanog'i) - faqat superadmin/director/menejer
        Route::get('/inventories/export/excel', [InventoryController::class, 'exportExcel'])->name('inventories.export.excel');
        Route::get('/inventories/export/pdf', [InventoryController::class, 'exportPdf'])->name('inventories.export.pdf');
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
    Route::get('/reports/reminders', [ReportController::class, 'reminders'])->name('reports.reminders')->middleware('can:reports.view');
    Route::get('/reports/branches', [ReportController::class, 'branches'])->name('reports.branches')->middleware('can:reports.view');

    // Profil boshqaruvi
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
