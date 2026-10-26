<?php

namespace Tests\Support;

use Stripe\HttpClient\ClientInterface;

/**
 * Stands in for Stripe's API: records every request the app makes and answers the two
 * calls a checkout needs (find or create the customer, create the Checkout Session).
 */
class FakeStripe implements ClientInterface
{
    /** @var list<array{method: string, path: string, params: array}> */
    public array $requests = [];

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $path = parse_url($absUrl, PHP_URL_PATH);
        $this->requests[] = ['method' => strtoupper($method), 'path' => $path, 'params' => $params];

        $body = match (true) {
            $path === '/v1/customers' => ['id' => 'cus_new', 'object' => 'customer'],
            str_starts_with($path, '/v1/customers/') => ['id' => basename($path), 'object' => 'customer'],
            $path === '/v1/checkout/sessions' => [
                'id' => 'cs_test_1',
                'object' => 'checkout.session',
                'url' => 'https://checkout.stripe.com/c/pay/cs_test_1',
            ],
            default => throw new \LogicException("Unexpected Stripe call: {$method} {$path}"),
        };

        return [json_encode($body), 200, []];
    }

    /** Prices sent to Stripe in Checkout Sessions. */
    public function checkoutPrices(): array
    {
        $prices = [];
        foreach ($this->requests as $request) {
            if ($request['path'] === '/v1/checkout/sessions') {
                foreach ($request['params']['line_items'] ?? [] as $item) {
                    $prices[] = $item['price'];
                }
            }
        }

        return $prices;
    }
}
