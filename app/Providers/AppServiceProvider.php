<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // The webhook route is defined in routes/web.php, with its signature check always on.
        Cashier::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Don't lock customers out while their payment is still being processed.
        Cashier::keepIncompleteSubscriptionsActive();
        Cashier::keepPastDueSubscriptionsActive();
    }
}
