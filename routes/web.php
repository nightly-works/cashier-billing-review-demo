<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\PaymentController;

Route::get('/', fn () => view('welcome'));
Route::view('/login', 'welcome')->name('login');
Route::get('/pricing', [BillingController::class, 'pricing']);

Route::middleware('auth')->group(function () {
    // Current user for the front end: only what the front end shows.
    Route::get('/me', fn (Request $request) => $request->user()->only(['id', 'name', 'email']));

    Route::post('/billing/checkout', [BillingController::class, 'checkout']);

    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/export', [ReportController::class, 'export']);
    Route::get('/reports/forecast', [ReportController::class, 'forecast'])->middleware('subscribed');
});

// Cashier's routes, registered here so the webhook uses StripeWebhookController.
Route::prefix('stripe')->name('cashier.')->group(function () {
    Route::get('payment/{id}', [PaymentController::class, 'show'])->name('payment');
    Route::post('webhook', [StripeWebhookController::class, 'handleWebhook'])->name('webhook');
});
