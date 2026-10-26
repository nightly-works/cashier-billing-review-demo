<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function pricing()
    {
        return view('pricing');
    }

    // The browser picks a plan by name; the Stripe price comes from the server's configuration.
    public function checkout(Request $request)
    {
        $plan = $request->validate(['plan' => ['required', 'in:basic,pro']])['plan'];

        return $request->user()
            ->newSubscription('default', config("billing.prices.{$plan}"))
            ->checkout([
                'success_url' => url('/reports'),
                'cancel_url' => url('/pricing'),
            ]);
    }
}
