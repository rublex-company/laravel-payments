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
