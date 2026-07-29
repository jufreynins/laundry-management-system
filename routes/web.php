<?php

use App\Http\Controllers\Admin\BusinessSettingsController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\DeliveryZoneController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPhotoController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->middleware('throttle:5,1')
        ->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('customers', CustomerController::class)->except(['destroy']);
    Route::resource('services', ServiceController::class)->except(['destroy']);
    Route::resource('orders', OrderController::class)->only(['index', 'create', 'store', 'show']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
    Route::patch('/orders/{order}/assign', [OrderController::class, 'assign'])->name('orders.assign');
    Route::post('/orders/{order}/photos', [OrderPhotoController::class, 'store'])->name('orders.photos.store');
    Route::get('/orders/{order}/photos/{photo}', [OrderPhotoController::class, 'show'])->name('orders.photos.show');
    Route::get('/orders/{order}/receipt', [ReceiptController::class, 'show'])->name('orders.receipt');
    Route::get('/orders/{order}/claim-ticket', [ReceiptController::class, 'claimTicket'])->name('orders.claim-ticket');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::post('/orders/{order}/payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::patch('/payments/{payment}/void', [PaymentController::class, 'void'])->name('payments.void');
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');

    Route::get('/deliveries', [DeliveryController::class, 'index'])->name('deliveries.index');
    Route::get('/orders/{order}/deliveries/create', [DeliveryController::class, 'create'])->name('deliveries.create');
    Route::post('/orders/{order}/deliveries', [DeliveryController::class, 'store'])->name('deliveries.store');
    Route::patch('/deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus'])->name('deliveries.status');
    Route::patch('/deliveries/{delivery}/driver', [DeliveryController::class, 'assignDriver'])->name('deliveries.driver');

    Route::get('/delivery-zones', [DeliveryZoneController::class, 'index'])->name('delivery-zones.index');
    Route::get('/delivery-zones/create', [DeliveryZoneController::class, 'create'])->name('delivery-zones.create');
    Route::post('/delivery-zones', [DeliveryZoneController::class, 'store'])->name('delivery-zones.store');

    Route::get('/driver/deliveries', [DriverController::class, 'index'])->name('driver.index');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['destroy']);
        Route::resource('locations', LocationController::class)->except(['destroy']);
        Route::get('/settings', [BusinessSettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings/{location}', [BusinessSettingsController::class, 'update'])->name('settings.update');
    });
});
