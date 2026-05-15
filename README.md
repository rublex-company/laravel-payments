# Laravel Rublex Payments

A Laravel package for the [Rublex Payment Gateway](https://panel.pay.rublex.io) — accept crypto and fiat payments through a single, terminal-scoped API.

Wraps every endpoint of the Rublex Merchant API behind a Laravel facade, plus a fluent invoice builder.

## Requirements

- PHP 8.1+
- Laravel 10+
- `guzzlehttp/guzzle ^7.0`

## Installation

```bash
composer require rublex/laravel-payments
php artisan vendor:publish --tag=config
php artisan migrate
```

## Build Your Integration With an AI Assistant

**Skip reading the rest of this doc.** Paste the whole `README.md` of this package plus the prompt below into Claude / Cursor / Copilot — the assistant will interview you, then wire the SDK into your Laravel app end to end.

```text
ROLE
You are a senior Laravel engineer. Your task is to integrate the Rublex Payment
Gateway into my Laravel app using the official `rublex/laravel-payments` SDK
(README provided above) and take me from zero to a production-ready integration.

SOURCE OF TRUTH
The Laravel SDK README provided above is your single source of truth.
  - USE the SDK. Do not hand-roll an HTTP client, Guzzle wrapper, or REST calls.
  - Only call methods that exist in the README (`RublexPayments::crypto()`, `fiat()`,
    `getSupportedCurrencies()`, `getCryptoInvoice()`, `getFiatInvoice()`, etc.).
  - Method signatures live in the README's tables — respect them exactly.
  - Every SDK call returns the `{ status, message, data }` envelope — handle that
    centrally (a small wrapper service or a thin DTO is fine).
  - Hosted invoice pages live on https://panel.pay.rublex.io and are baked into the
    `data.invoice_url` you receive. Redirect customers there as-is — never rewrite it.
  - If something I ask for is not covered by the SDK or the README is silent on it,
    STOP and tell me. Never invent endpoints, parameters, or response fields.

CRYPTO INVOICE — NON-NEGOTIABLE PRE-FLIGHT
Before you call `RublexPayments::crypto()->...->createInvoice()` (or
`RublexPayments::createCryptoInvoice(...)`) you MUST:
  1. Call `RublexPayments::getSupportedCurrencies()` first.
  2. Pick a `currency_id` from THAT response (the terminal-approved list).
  3. Pass it as the argument to `->pick($currencyId)` or as `currency_id` in the array form.
Never hard-code numeric currency IDs. The terminal rejects IDs it has not approved
with HTTP 422 — surface that as a clear error and refetch the list on retry.

RETURN URLS — success_url AND failed_url
Every invoice flow accepts optional `success_url` / `failed_url` (builder methods
`->success(...)`, `->failed(...)`, or `->returnTo(...)` for the same URL on both).
Pass them from the checkout. The redirect is UX only and is NOT proof of payment —
order state must come from the webhook + a server-side status lookup.

WORK IN TWO PHASES.

──────────────────────────────────────────────
PHASE 1 — INTERVIEW ME (no code yet)
Ask me the questions below in ONE grouped message, give a short recommendation
where you can, then STOP and wait for my answers.
  1. Payment types: crypto, fiat, or both?
  2. Flow: explain the trade-offs between
       - Crypto · Pay Request          (`->crypto()->pick($id)->createInvoice()`)
       - Fiat · Direct gateway         (`->fiat()->pick($gatewayId)->createInvoice()`)
       - Fiat · Gateway selection      (`->fiat()->byPayer()->createInvoice()` + payer endpoints)
     and recommend the best fit.
  3. Laravel version, PHP version, and any existing Order/Payment models I should
     plug into.
  4. Where should the terminal token live? (`.env`, Vault, AWS Secrets Manager, …)
  5. Which route should be my `callback_url`, and which page should `success_url` /
     `failed_url` point to? (Same URL for both is fine — recommended.)
  6. Scope: which pieces do I need — checkout controller that creates an invoice
     and redirects, webhook controller, Order/Payment Eloquent models + migration,
     status reconciliation job (queued), an admin/status view, automated tests?
  7. Greenfield or fitting into code I'll paste?
  8. Separate staging/production terminals?

──────────────────────────────────────────────
PHASE 2 — BUILD IT (after I confirm)
Deliverables, all idiomatic Laravel:
  a. Service layer — a thin `RublexPaymentsService` (or similar) that wraps the
     SDK methods I need and centralises envelope parsing + logging. NO direct
     HTTP calls.
  b. Invoice creation
     - Crypto: pull `getSupportedCurrencies()` (cached briefly), pick the right
       `currency_id`, build the invoice via the SDK with `amount`, `callback`,
       `success`, `failed`, and persist `invoice_number` on my Order.
     - Fiat: same pattern with `gateway_id` (or `byPayer()` for selection).
     - Redirect the customer to `$response['data']['invoice_url']` as-is.
  c. Webhook controller
     - `POST` route, responds `200 OK` within 10s.
     - Treats the callback as UNTRUSTED — re-fetches via `getCryptoInvoice()` /
       `getFiatInvoice()` before marking the order paid.
     - Idempotent — the same callback may be retried; check Order status before
       transitioning.
     - Maps PENDING / PARTIAL / PAID / EXPIRED / CANCELLED → my order state.
  d. Return-URL handler
     - Reads `invoice_number` from the query string, looks up MY order, renders
       a status page from the local record. Never trust the redirect to confirm
       payment.
  e. Config + errors
     - `config/rublex_payments.php` already published by the SDK — point to the
       extra env vars I need.
     - Handles 400, 401, 403, 404, 422, 429, 5xx. Retry with exponential backoff
       on 429 / 5xx (use a queued job). On 422 from crypto, refetch the supported
       currencies and report a clear error if my chosen `currency_id` is gone.
  f. Security
     - Token only in env / Vault, never in code or front-end.
     - Log `invoice_number` next to my internal order id.
     - HTTPS on every URL (callback / success / failed).
  g. Optional but recommended: a `php artisan rublex:reconcile-invoices` command
     that lists open invoices and pulls fresh status — guards against missed webhooks.

OUTPUT FORMAT
  - Show the full file tree first, then each file in its own code block.
  - Migrations, models, controllers, routes, service, config, tests.
  - Setup steps + env vars + how to run.
  - Finally, ask whether I want automated tests (Pest/PHPUnit) or any adjustments.

Begin with PHASE 1 now.
```

> **Tip:** Once the assistant finishes Phase 2, ask it to add a queued reconciliation job and Pest tests for the webhook — both small, both worth having.

## Configuration

Add to your `.env`:

```env
RUBLEX_PAYMENTS_API_KEY=<your-60-char-terminal-token>
RUBLEX_PAYMENTS_CALLBACK_URL=https://your-site.com/rublex/callback
# Override only if Rublex tells you to:
# RUBLEX_PAYMENTS_URL=https://api.pay.rublex.io/terminals/v1/
```

Grab the terminal token from your Rublex merchant panel under *Stores → Terminals → Token*.

## Invoice creation

> **Crypto pre-flight is mandatory.** Before creating a crypto invoice you MUST call `getSupportedCurrencies()` and use one of the returned `id` values as `currency_id`. The terminal rejects IDs it has not approved with HTTP `422`. Do not hard-code IDs.

The package exposes two equivalent ways to create an invoice — pick whichever fits your code style.

### Fluent builder

```php
use Rublex\Payments\Facades\RublexPayments;

// 1) Look up which currencies this terminal supports.
$currencyId = RublexPayments::getSupportedCurrencies()['data'][0]['id'];

// 2) Crypto invoice — merchant locks the coin
RublexPayments::crypto()
    ->amount(0.5)
    ->pick($currencyId)                          // from /currencies/supported
    ->callback('https://your-site.com/rublex/callback')
    ->returnTo('https://your-site.com/checkout/return')
    ->createInvoice();

// Fiat — merchant locks the gateway (fixed_rate defaults to true)
RublexPayments::fiat()
    ->amount(19.99)
    ->pick(4)                                    // gateway_id
    ->callback('https://your-site.com/rublex/callback')
    ->success('https://your-site.com/checkout/success')
    ->failed('https://your-site.com/checkout/cancelled')
    ->customer(email: 'buyer@example.com', firstName: 'Ada')
    ->createInvoice();

// Fiat — payer picks the gateway, floating FX rate
RublexPayments::fiat()
    ->amount(19.99)
    ->byPayer()
    ->lockRate(false)
    ->returnTo('https://your-site.com/checkout/return')
    ->createInvoice();
```

> Crypto invoices always lock the coin on the merchant side. The payer-selected crypto flow (Smart Payments) has been retired.

| Method | Purpose |
|---|---|
| `crypto()` / `fiat()` | Start a builder chain. |
| `amount($n)` | Invoice amount. |
| `pick($id)` | Merchant locks the coin (crypto) or gateway (fiat). |
| `byPayer()` | Fiat only — hand the gateway choice over to the payer at checkout. |
| `callback($url)` | Override the default webhook URL. |
| `success($url)` | Where the hosted page sends the payer after a successful payment. |
| `failed($url)` | Where the hosted page sends the payer after a failed/cancelled/expired payment. |
| `returnTo($url)` | Shortcut: same URL for success and failure (recommended). |
| `lockRate($bool = true)` | Fiat only — locks or floats the FX rate. Defaults to locked. |
| `customer(email:, firstName:, lastName:, mobile:)` | Fiat only — pre-fill payer details. |
| `createInvoice([$extras])` | Send the request; any extra keys are merged in. |

### Array form

```php
use Rublex\Payments\Facades\RublexPayments;

$currencyId = RublexPayments::getSupportedCurrencies()['data'][0]['id'];

// Crypto — merchant locks the coin
RublexPayments::createCryptoInvoice([
    'amount'      => 0.5,
    'currency_id' => $currencyId,
    'success_url' => 'https://your-site.com/checkout/return',
    'failed_url'  => 'https://your-site.com/checkout/return',
]);

// Fiat — merchant locks the gateway
RublexPayments::createFiatInvoice([
    'amount'         => 19.99,
    'gateway_id'     => 4,
    'fixed_rate'     => true,
    'customer_email' => 'buyer@example.com',
    'success_url'    => 'https://your-site.com/checkout/return',
    'failed_url'     => 'https://your-site.com/checkout/return',
]);

// Fiat — payer picks the gateway
RublexPayments::createFiatInvoice([
    'amount'      => 19.99,
    'fixed_rate'  => false,
    'success_url' => 'https://your-site.com/checkout/return',
    'failed_url'  => 'https://your-site.com/checkout/return',
], payerChoice: true);
```

Each call returns the decoded Rublex envelope:

```json
{
  "status": "SUCCESS",
  "message": "request.successful",
  "data": {
    "invoice_number": "BpXo8T60vIN9D7NCcs66rOnZVipBLUah",
    "invoice_url":    "https://panel.pay.rublex.io/payment?invoice_number=BpXo8T60vIN9D7NCcs66rOnZVipBLUah",
    "amount":         "0.50000000",
    "paid_amount":    "0.00000000",
    "status":         "PENDING"
  }
}
```

Redirect the buyer to `data.invoice_url` to finish payment.

> **`success_url` / `failed_url` are UX, not proof of payment.** Always reconcile against the webhook or `getCryptoInvoice()` / `getFiatInvoice()`.

## Catalog & lookup

```php
RublexPayments::getInformation();                    // GET  /info
RublexPayments::getCurrencies($page, $perPage);      // GET  /currencies
RublexPayments::getSupportedCurrencies();            // GET  /currencies/supported
RublexPayments::getFiatGateways();                   // GET  /fiat/gateways
RublexPayments::getFiatCurrencies();                 // GET  /fiat/currencies

RublexPayments::getCryptoInvoice($invoiceNumber);    // GET  /invoices?invoice_number=…
RublexPayments::listCryptoInvoices([...]);           // GET  /invoices
RublexPayments::listPayRequests([...]);              // GET  /pay-requests
RublexPayments::getFiatInvoice($invoiceNumber);      // GET  /fiat/invoices?invoice_number=…
RublexPayments::listFiatInvoices([...]);             // GET  /fiat/invoices
```

## Payer-facing actions

These two endpoints are reached from the hosted invoice page and authenticate via the `invoice_number` itself — no `Token` header is sent.

```php
// Fiat Gateway-Selection: list available gateways
RublexPayments::listFiatInvoiceGateways($invoiceNumber);

// Fiat Gateway-Selection: lock the buyer's choice
RublexPayments::selectFiatGateway($invoiceNumber, [
    'gateway_id'     => 4,
    'customer_email' => 'buyer@example.com',
]);
```

## Callbacks (webhooks)

Rublex posts to your `callback_url` on every status change:

```json
{
  "invoice_number": "BpXo8T60vIN9D7NCcs66rOnZVipBLUah",
  "status":         "PAID",
  "amount":         "0.50000000",
  "paid_amount":    "0.50000000",
  "currency":       "USDT (TRC20)"
}
```

> Treat callbacks as untrusted. Always re-fetch the invoice via `getCryptoInvoice()` / `getFiatInvoice()` before marking the order paid.

## Invoice lifecycle

```
PENDING ──► PARTIAL ──► PAID
   │            │
   │            └──► EXPIRED
   └──► CANCELLED
```

## Resources

- [GitHub Repository](https://github.com/rublex-company/laravel-payments)
- [Merchant Panel](https://panel.pay.rublex.io)
- [API Documentation](https://github.com/rublexgit/pay)
- Support: <support@rublex.io>

## License

MIT — see [LICENSE.md](LICENSE.md).
