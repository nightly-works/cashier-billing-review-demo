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

        // A second Checkout would bill both plans; plan changes go through the billing portal.
        $current = $request->user()->subscription('default');
        if ($current && ! $current->ended()) {
            return redirect('/pricing')->withErrors(['plan' => 'You already have a plan: change it from the billing portal.']);
        }

        return $request->user()
            ->newSubscription('default', config("billing.prices.{$plan}"))
            ->checkout([
                'success_url' => url('/reports'),
                'cancel_url' => url('/pricing'),
            ]);
    }
}
