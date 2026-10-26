<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BillingHelpers;
use Tests\TestCase;

/** What paying customers do must keep working after the fixes. */
class NormalUseTest extends TestCase
{
    use BillingHelpers, RefreshDatabase;

    private const SECRET = 'whsec_demo_0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBilling();
        config(['cashier.webhook.secret' => self::SECRET]);
    }

    public function test_a_signed_stripe_event_opens_pro_features(): void
    {
        $bob = $this->customer('cus_bob');
        $payload = $this->subscriptionCreatedEvent('cus_bob', 'price_pro_monthly');

        $this->postWebhook($payload, ['Stripe-Signature' => $this->stripeSignature($payload, self::SECRET)])->assertOk();

        $this->actingAs($bob)->get('/reports/forecast')->assertOk()->assertJson(['next_month_cents' => 40000]);
        $export = $this->actingAs($bob)->get('/reports/export')->assertOk();
        $this->assertStringContainsString('"Invoice 1042",1200.00', $export->streamedContent());
    }

    public function test_an_event_signed_with_another_secret_is_refused(): void
    {
        $bob = $this->customer('cus_bob');
        $payload = $this->subscriptionCreatedEvent('cus_bob', 'price_pro_monthly');

        $this->postWebhook($payload, ['Stripe-Signature' => $this->stripeSignature($payload, 'whsec_someone_else_000000')])->assertForbidden();
        $this->postWebhook($payload)->assertForbidden();

        $this->assertDatabaseMissing('subscriptions', ['user_id' => $bob->id]);
    }

    public function test_checkout_sends_the_configured_price_of_each_plan(): void
    {
        $bob = $this->customer();

        $this->actingAs($bob)->post('/billing/checkout', ['plan' => 'pro'])
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_1');
        $this->actingAs($bob)->post('/billing/checkout', ['plan' => 'basic']);

        $this->assertSame(['price_pro_monthly', 'price_basic_monthly'], $this->stripe->checkoutPrices());
    }

    public function test_a_pro_subscriber_on_trial_uses_pro_features(): void
    {
        $bob = $this->customer();
        $this->subscribe($bob, 'price_pro_monthly', 'trialing');

        $this->actingAs($bob)->get('/reports/forecast')->assertOk();
        $this->actingAs($bob)->get('/reports')->assertOk()->assertSee('Export CSV');
    }

    public function test_a_basic_subscriber_sees_the_upgrade_offer_and_their_profile(): void
    {
        $bob = $this->customer();
        $this->subscribe($bob, 'price_basic_monthly');

        $this->actingAs($bob)->get('/reports')->assertOk()->assertDontSee('Export CSV')->assertSee('Pro');
        $this->actingAs($bob)->getJson('/me')->assertExactJson(['id' => $bob->id, 'name' => $bob->name, 'email' => $bob->email]);
    }
}
