<?php

namespace Tests\Support;

use App\Models\User;
use Stripe\ApiRequestor;

trait BillingHelpers
{
    protected FakeStripe $stripe;

    protected function setUpBilling(): void
    {
        config([
            'cashier.secret' => 'sk_test_demo',
            'billing.prices.basic' => 'price_basic_monthly',
            'billing.prices.pro' => 'price_pro_monthly',
        ]);
        $this->stripe = new FakeStripe;
        ApiRequestor::setHttpClient($this->stripe);
    }

    protected function customer(string $stripeId = 'cus_bob'): User
    {
        $user = User::factory()->create(['stripe_id' => $stripeId]);
        $user->transactions()->create(['booked_on' => now()->subDays(3), 'label' => 'Invoice 1042', 'amount_cents' => 120000]);

        return $user;
    }

    protected function subscribe(User $user, string $price, string $status = 'active'): void
    {
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_'.$user->id.'_'.$status,
            'stripe_status' => $status,
            'stripe_price' => $price,
            'quantity' => 1,
        ]);
    }

    /** A customer.subscription.created event, trimmed to the fields Cashier reads. */
    protected function subscriptionCreatedEvent(string $customer, string $price): string
    {
        return json_encode([
            'id' => 'evt_'.bin2hex(random_bytes(6)),
            'type' => 'customer.subscription.created',
            'data' => ['object' => [
                'id' => 'sub_forged',
                'customer' => $customer,
                'status' => 'active',
                'metadata' => ['type' => 'default'],
                'items' => ['data' => [[
                    'id' => 'si_1',
                    'quantity' => 1,
                    'price' => ['id' => $price, 'product' => 'prod_ledgerly'],
                ]]],
            ]],
        ]);
    }

    /** Stripe-Signature header for a payload, signed with a webhook secret. */
    protected function stripeSignature(string $payload, string $secret): string
    {
        $t = time();

        return "t={$t},v1=".hash_hmac('sha256', "{$t}.{$payload}", $secret);
    }

    protected function postWebhook(string $payload, array $headers = [])
    {
        return $this->call('POST', '/stripe/webhook', [], [], [], array_merge(
            ['CONTENT_TYPE' => 'application/json'],
            collect($headers)->mapWithKeys(fn ($v, $k) => ['HTTP_'.strtoupper(str_replace('-', '_', $k)) => $v])->all()
        ), $payload);
    }
}
