<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pro features need a valid subscription to the Pro price itself: any subscription is
 * not enough, since Basic and any other price in the Stripe account are subscriptions too.
 */
class EnsureProPlan
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasPro()) {
            return redirect('/pricing');
        }

        return $next($request);
    }
}
