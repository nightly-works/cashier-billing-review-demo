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
        // Cashier's defaults stay: a subscription whose first payment never succeeded
        // (incomplete) or whose renewal failed (past_due) does not open paid features.
    }
}
