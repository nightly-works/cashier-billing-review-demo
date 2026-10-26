<?php

return [
    // Stripe price ids of the two monthly plans shown on the pricing page.
    'prices' => [
        'basic' => env('STRIPE_PRICE_BASIC'),
        'pro' => env('STRIPE_PRICE_PRO'),
    ],
];
