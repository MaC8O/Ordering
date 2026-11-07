<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MenuController as AdminMenuController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MyOrderController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PreorderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront (public, no login)
|--------------------------------------------------------------------------
*/

// Language switch (EN / TH)
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, config('ordering.locales', ['en', 'th']), true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('lang.switch');

Route::get('/', [MenuController::class, 'index'])->name('menu.index');
Route::get('/item/{menuItem}', [MenuController::class, 'show'])->name('menu.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('checkout.store');

// Weekly preorder batch (open only during the configured collection window).
Route::get('/preorder', [PreorderController::class, 'create'])->name('preorder.create');
Route::post('/preorder', [PreorderController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('preorder.store');

Route::get('/order/{order:public_token}', [OrderController::class, 'show'])->name('order.show');
Route::post('/order/{order:public_token}/receipt', [OrderController::class, 'uploadReceipt'])
    ->middleware('throttle:10,1')
    ->name('order.receipt');

Route::get('/my-order', [MyOrderController::class, 'index'])->name('my-order.index');
Route::post('/my-order', [MyOrderController::class, 'lookup'])
    ->middleware('throttle:15,1')
    ->name('my-order.lookup');

Route::get('/about', [AboutController::class, 'index'])->name('about');

/*
|--------------------------------------------------------------------------
| Customer accounts (optional) — loyalty & order history
|--------------------------------------------------------------------------
*/

Route::middleware('guest:customer')->group(function () {
    Route::get('/register', [CustomerAuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [CustomerAuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.attempt');
    Route::get('/login', [CustomerAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [CustomerAuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.attempt');
});

Route::middleware('auth:customer')->group(function () {
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::post('/logout', [CustomerAuthController::class, 'logout'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function () {
    // Guest (login) routes
    Route::middleware('guest:admin')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'show'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'login'])
            ->middleware('throttle:5,1')
            ->name('login.attempt');
    });

    // Authenticated admin routes
    Route::middleware('auth:admin')->group(function () {
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/accepting', [DashboardController::class, 'toggleAccepting'])->name('accepting.toggle');

        // Preorder batch (kitchen prep)
        Route::get('/preorders', [\App\Http\Controllers\Admin\PreorderController::class, 'index'])->name('preorders.index');

        // Orders
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/ticket', [AdminOrderController::class, 'ticket'])->name('orders.ticket');
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
        Route::patch('/orders/{order}/payment', [AdminOrderController::class, 'updatePayment'])->name('orders.payment');
        Route::patch('/orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->name('orders.cancel');

        // Menu management
        Route::get('/menu', [AdminMenuController::class, 'index'])->name('menu.index');

        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::patch('/categories/{category}/toggle', [CategoryController::class, 'toggle'])->name('categories.toggle');
        Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::post('/items', [MenuItemController::class, 'store'])->name('items.store');
        Route::patch('/items/{menuItem}', [MenuItemController::class, 'update'])->name('items.update');
        Route::patch('/items/{menuItem}/toggle', [MenuItemController::class, 'toggle'])->name('items.toggle');
        Route::delete('/items/{menuItem}', [MenuItemController::class, 'destroy'])->name('items.destroy');

        // Settings
        Route::get('/settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::patch('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/qr', [SettingController::class, 'uploadQr'])->name('settings.qr');
        Route::patch('/settings/schedule', [SettingController::class, 'updateSchedule'])->name('settings.schedule');
        Route::patch('/settings/preorder', [SettingController::class, 'updatePreorder'])->name('settings.preorder');
    });
});
