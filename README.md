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

The package exposes two equivalent ways to create an invoice — pick whichever fits your code style.

### Fluent builder

```php
use Rublex\Payments\Facades\RublexPayments;

// Crypto — merchant locks the coin
RublexPayments::crypto()
    ->amount(0.5)
    ->pick(3)                          // currency_id
    ->callback('https://your-site.com/rublex/callback')
    ->createInvoice();

// Crypto — payer picks the coin (3 = payout coin)
RublexPayments::crypto()
    ->amount(50)
    ->pick(3)
    ->byPayer()
    ->createInvoice();

// Fiat — merchant locks the gateway (fixed_rate defaults to true)
RublexPayments::fiat()
    ->amount(19.99)
    ->pick(4)                          // gateway_id
    ->customer(email: 'buyer@example.com', firstName: 'Ada')
    ->createInvoice();

// Fiat — payer picks the gateway, floating FX rate
RublexPayments::fiat()
    ->amount(19.99)
    ->byPayer()
    ->lockRate(false)
    ->createInvoice();
```

| Method | Purpose |
|---|---|
| `crypto()` / `fiat()` | Start a builder chain. |
| `amount($n)` | Invoice amount. |
| `pick($id)` | Merchant locks the coin (crypto) or gateway (fiat). |
| `byPayer()` | Hand the choice over to the payer at checkout. |
| `callback($url)` | Override the default webhook URL. |
| `lockRate($bool = true)` | Fiat only — `lockRate()` locks the FX rate, `lockRate(false)` lets it float. Defaults to locked. |
| `customer(email:, firstName:, lastName:, mobile:)` | Fiat only — pre-fill payer details. |
| `createInvoice([$extras])` | Send the request; any extra keys are merged in. |

### Array form

```php
use Rublex\Payments\Facades\RublexPayments;

// Crypto — merchant locks the coin
RublexPayments::createCryptoInvoice([
    'amount'      => 0.5,
    'currency_id' => 3,
]);

// Crypto — payer picks the coin
RublexPayments::createCryptoInvoice([
    'amount'      => 50,
    'currency_id' => 3,
], payerChoice: true);

// Fiat — merchant locks the gateway
RublexPayments::createFiatInvoice([
    'amount'         => 19.99,
    'gateway_id'     => 4,
    'fixed_rate'     => true,
    'customer_email' => 'buyer@example.com',
]);

// Fiat — payer picks the gateway
RublexPayments::createFiatInvoice([
    'amount'     => 19.99,
    'fixed_rate' => false,
], payerChoice: true);
```

Each call returns the decoded Rublex envelope:

```json
{
  "status": "SUCCESS",
  "message": "request.successful",
  "data": {
    "invoice_number": "X576Kz…",
    "invoice_url":    "https://panel.pay.rublex.io/invoices/X576Kz…",
    "amount":         "0.50000000",
    "paid_amount":    "0.00000000",
    "status":         "PENDING"
  }
}
```

Redirect the buyer to `data.invoice_url` to finish payment.

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

These three endpoints are reached from the hosted invoice page and authenticate via the `invoice_number` itself — no `Token` header is sent.

```php
// Crypto Smart-Payments: lock the payment coin
RublexPayments::selectCryptoCurrency($invoiceNumber, $currencyId);

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
  "invoice_number": "X576Kz…",
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

- [Merchant Panel](https://panel.pay.rublex.io)
- [API Documentation](https://panel.pay.rublex.io/docs)
- Support: <support@rublex.io>

## License

MIT — see [LICENSE.md](LICENSE.md).
