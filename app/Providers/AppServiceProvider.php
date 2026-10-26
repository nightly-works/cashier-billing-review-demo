<?php

namespace App\Providers;

use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Http\Controllers\PaymentController;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Cashier's routes are registered in boot(), so the webhook uses StripeWebhookController.
        Cashier::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Same paths and names as Cashier's own routes, outside the web group (no session, no CSRF).
        Route::prefix(config('cashier.path'))->name('cashier.')->group(function () {
            Route::get('payment/{id}', [PaymentController::class, 'show'])->name('payment');
            Route::post('webhook', [StripeWebhookController::class, 'handleWebhook'])->name('webhook');
        });

        // Cashier's defaults stay: a subscription whose first payment never succeeded
        // (incomplete) or whose renewal failed (past_due) does not open paid features.
    }
}
