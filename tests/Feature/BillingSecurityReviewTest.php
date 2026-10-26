<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BillingHelpers;
use Tests\TestCase;

/**
 * One test per finding of the review. Each one is an attack: it fails on the app as
 * delivered (tag as-delivered, see scripts/prove-flaws.sh) and passes after the fixes.
 */
class BillingSecurityReviewTest extends TestCase
{
    use BillingHelpers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBilling();
    }

    // Finding 1: STRIPE_WEBHOOK_SECRET is not in .env.example, so production runs without it.
    public function test_1_a_forged_webhook_does_not_grant_pro_when_the_secret_is_missing(): void
    {
        config(['cashier.webhook.secret' => null]);
        $bob = $this->customer('cus_bob');

        $response = $this->postWebhook($this->subscriptionCreatedEvent('cus_bob', 'price_pro_monthly'));

        $this->assertDatabaseMissing('subscriptions', ['user_id' => $bob->id]);
        $response->assertStatus(500);
        $this->actingAs($bob)->get('/reports/forecast')->assertRedirect('/pricing');
    }

    // The same with STRIPE_WEBHOOK_SECRET= present but empty: Cashier skips the check too, and
    // a fix that only forced the check on would accept this signature made with an empty key.
    public function test_1b_a_webhook_signed_with_an_empty_secret_is_refused(): void
    {
        config(['cashier.webhook.secret' => '']);
        $bob = $this->customer('cus_bob');
        $payload = $this->subscriptionCreatedEvent('cus_bob', 'price_pro_monthly');

        $response = $this->postWebhook($payload, ['Stripe-Signature' => $this->stripeSignature($payload, '')]);

        $this->assertDatabaseMissing('subscriptions', ['user_id' => $bob->id]);
        $response->assertStatus(500);
    }

    // The forged event needs the victim's Stripe customer id; /me handed it out.
    public function test_1c_the_front_end_endpoint_does_not_expose_the_stripe_customer_id(): void
    {
        $bob = $this->customer('cus_bob');

        $me = $this->actingAs($bob)->getJson('/me')->assertOk()->json();

        $this->assertSame($bob->email, $me['email']);
        $this->assertArrayNotHasKey('stripe_id', $me);
        $this->assertStringNotContainsString('cus_bob', json_encode($me));
    }

    // Finding 2: the price comes from the form, so any price in the Stripe account can be bought.
    public function test_2_checkout_does_not_accept_a_price_chosen_by_the_browser(): void
    {
        $bob = $this->customer();

        $response = $this->actingAs($bob)->post('/billing/checkout', ['price' => 'price_legacy_1eur_yearly']);
        $this->assertNotContains('price_legacy_1eur_yearly', $this->stripe->checkoutPrices());
        $response->assertSessionHasErrors('plan');

        // Next to a valid plan, the smuggled price is ignored.
        $this->actingAs($bob)->post('/billing/checkout', ['plan' => 'pro', 'price' => 'price_legacy_1eur_yearly']);
        $this->assertSame(['price_pro_monthly'], $this->stripe->checkoutPrices());
    }

    // Found in the review pass: a second Checkout billed both plans.
    public function test_2c_a_subscriber_cannot_start_a_second_subscription(): void
    {
        $bob = $this->customer();
        $this->subscribe($bob, 'price_pro_monthly');

        $this->actingAs($bob)->post('/billing/checkout', ['plan' => 'basic'])->assertSessionHasErrors('plan');

        $this->assertSame([], $this->stripe->checkoutPrices());
    }

    public function test_2b_a_basic_subscription_does_not_open_pro_features(): void
    {
        $bob = $this->customer();
        $this->subscribe($bob, 'price_basic_monthly');

        $this->actingAs($bob)->get('/reports/forecast')->assertRedirect('/pricing');
    }

    // Finding 3: the export link is hidden in the page, the route is not protected.
    public function test_3_a_free_user_cannot_export_by_opening_the_url(): void
    {
        $bob = $this->customer();

        $this->actingAs($bob)->get('/reports/export')->assertRedirect('/pricing');
    }

    // Finding 4: subscriptions whose payment failed kept Pro.
    public function test_4_a_subscription_whose_first_payment_never_succeeded_does_not_open_pro(): void
    {
        $bob = $this->customer();
        $this->subscribe($bob, 'price_pro_monthly', 'incomplete');

        $this->actingAs($bob)->get('/reports/forecast')->assertRedirect('/pricing');
    }

    public function test_4b_a_past_due_subscription_does_not_open_pro(): void
    {
        $bob = $this->customer();
        $this->subscribe($bob, 'price_pro_monthly', 'past_due');

        $this->actingAs($bob)->get('/reports/forecast')->assertRedirect('/pricing');
    }
}
