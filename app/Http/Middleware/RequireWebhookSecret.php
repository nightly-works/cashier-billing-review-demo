<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cashier checks webhook signatures only when a secret is configured, and forcing the check
 * on with an empty secret would accept anything signed with an empty key. Without a real
 * secret no event is processed; Stripe retries failed deliveries (up to 3 days in live mode),
 * so events are kept if the secret is set within that window.
 */
class RequireWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_string(config('cashier.webhook.secret')) || strlen(config('cashier.webhook.secret')) < 16) {
            Log::critical('Stripe webhook refused: STRIPE_WEBHOOK_SECRET is missing or too short.');

            return response('Webhook secret not configured', 500);
        }

        return $next($request);
    }
}
