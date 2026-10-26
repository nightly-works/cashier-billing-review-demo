# Laravel Cashier billing review (demo)

A proof piece for reviews of Laravel SaaS billing. A fictional bookkeeping app written for this demo (Laravel 13, Cashier 16, PHP 8.3) with a Basic and a Pro plan sold through Stripe Checkout, four flaws, each written as an attack test that works on the app as delivered and is refused after the fix.

| # | Flaw | Severity | Fix |
|---|---|---|---|
| 1 | No webhook secret, so Cashier skips the signature check: a fake "subscription created" event gives Pro; `/me` hands out the Stripe customer id it needs | critical | webhook always signature-checked and refused without a real secret; billing columns hidden |
| 2 | Checkout takes the Stripe price from a hidden field, and Pro is checked with `subscribed()`: any price, even Basic, opens Pro | high | plan name validated, price from config, `subscribedToPrice()` |
| 3 | The export link is hidden for free users; the route is not protected | high | Pro middleware on the route |
| 4 | `keepIncompleteSubscriptionsActive()` and `keepPastDueSubscriptionsActive()`: failed payments keep Pro | medium | Cashier's defaults back |

Full write-up, in the format a client would receive: [docs/security-review.md](docs/security-review.md).

## History

1. `as-delivered` (tag): the app as written for the demo, flaws included.
2. One commit per fix.
3. The findings as attack tests (all fail on the code as delivered) and normal-use tests, with a fake Stripe API that records what the app sends.

## Run

PHP 8.3 (with bcmath) and Composer:

```
composer install
cp .env.example .env && php artisan key:generate
php artisan test               # fixed app: every attack refused, normal use works
./scripts/prove-flaws.sh       # app as delivered: every attack works
```

No Stripe account or network access is needed. CI runs both, plus `composer audit`.

[![checks](https://github.com/nightly-works/cashier-billing-review-demo/actions/workflows/checks.yml/badge.svg)](https://github.com/nightly-works/cashier-billing-review-demo/actions/workflows/checks.yml)

Built by an AI agent (Claude) at [Nightly Works](https://github.com/nightly-works). MIT licence.
