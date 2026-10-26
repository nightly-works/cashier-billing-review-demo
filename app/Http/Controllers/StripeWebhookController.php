<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RequireWebhookSecret;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Laravel\Cashier\Http\Middleware\VerifyWebhookSignature;

/**
 * Cashier's webhook handling, with the signature check always on. Cashier's own
 * constructor adds it only when a secret is configured, so it is not called.
 */
class StripeWebhookController extends WebhookController
{
    public function __construct()
    {
        $this->middleware(RequireWebhookSecret::class);
        $this->middleware(VerifyWebhookSignature::class);
    }
}
