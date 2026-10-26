<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cashier checks webhook signatures only when a secret is configured, and an empty secret
 * signs nothing anyone else could not sign. Without a real secret no event is processed:
 * the 500 makes Stripe retry, so events sent before the secret is set are not lost.
 */
class RequireWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! is_string(config('cashier.webhook.secret')) || strlen(config('cashier.webhook.secret')) < 16) {
            Log::critical('Stripe webhook refused: STRIPE_WEBHOOK_SECRET is not set.');

            return response('Webhook secret not configured', 500);
        }

        return $next($request);
    }
}
