<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function pricing()
    {
        return view('pricing', ['prices' => config('billing.prices')]);
    }

    public function checkout(Request $request)
    {
        return $request->user()
            ->newSubscription('default', $request->input('price'))
            ->checkout([
                'success_url' => url('/reports'),
                'cancel_url' => url('/pricing'),
            ]);
    }
}
