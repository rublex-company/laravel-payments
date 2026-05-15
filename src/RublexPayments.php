<?php

namespace Rublex\Payments;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Rublex\Payments\Models\Logger;

/*
 * This file is part of the Laravel Rublex Payments package.
 *
 * (c) Rublex Team <payments@rublex.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class RublexPayments
{
    final public const VERSION = '1.2.1';

    protected string $apiKey;

    protected Client $client;

    protected Client $publicClient;

    protected mixed $response;

    protected string $baseUrl;

    protected ?string $callbackUrl;

    public function __construct()
    {
        $apiKey = Config::get('rublex_payments.apiKey');
        if (empty($apiKey)) {
            throw new IsNullException('API key not set');
        }

        $this->apiKey      = $apiKey;
        $this->baseUrl     = rtrim(Config::get('rublex_payments.liveUrl'), '/') . '/';
        $this->callbackUrl = Config::get('rublex_payments.callbackUrl');

        $this->client = new Client([
            'base_uri'    => $this->baseUrl,
            'http_errors' => false,
            'headers'     => [
                'Token'        => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ]);

        $this->publicClient = new Client([
            'base_uri'    => $this->baseUrl,
            'http_errors' => false,
            'headers'     => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ]);
    }

    // ---------------------------------------------------------------------
    // Invoice creation (4 entry points)
    // ---------------------------------------------------------------------

    public function crypto(): InvoiceBuilder
    {
        return new InvoiceBuilder($this, InvoiceBuilder::TYPE_CRYPTO);
    }

    public function fiat(): InvoiceBuilder
    {
        return new InvoiceBuilder($this, InvoiceBuilder::TYPE_FIAT);
    }

    /**
     * Crypto invoice → POST /terminals/v1/pay-request.
     *
     * Merchant-fixed currency is the only crypto flow exposed. The payer-
     * selected currency flow (Smart Payments) has been retired.
     *
     * The `currency_id` MUST come from a prior call to
     * {@see self::getSupportedCurrencies()} — the terminal rejects any id it
     * has not enabled with HTTP 422.
     *
     * @param array{
     *     amount:        float|int,
     *     currency_id:   int,
     *     callback_url?: string|null,
     *     success_url?:  string|null,
     *     failed_url?:   string|null,
     * } $data
     * @throws IsNullException
     */
    public function createCryptoInvoice(array $data): array
    {
        $data['callback_url'] = $data['callback_url'] ?? $this->callbackUrl;

        return $this->request('pay-request', 'POST', $data)->getResponse();
    }

    /**
     * Fiat invoice.
     *
     *   $payerChoice = false → POST /terminals/v1/fiat/pay-request-direct
     *     Merchant locks the gateway via `gateway_id`; payer sent straight to it.
     *
     *   $payerChoice = true  → POST /terminals/v1/fiat/pay-request-selection
     *     `gateway_id` is an optional default; payer picks the gateway on the
     *     hosted invoice page.
     *
     * @param array{
     *     amount: float|int,
     *     gateway_id?: int,
     *     callback_url?: string|null,
     *     success_url?: string|null,
     *     failed_url?: string|null,
     *     fixed_rate?: bool,
     *     customer_email?: string,
     *     customer_first_name?: string,
     *     customer_last_name?: string,
     *     customer_mobile?: string,
     * } $data
     * @throws IsNullException
     */
    public function createFiatInvoice(array $data, bool $payerChoice = false): array
    {
        $data['callback_url'] = $data['callback_url'] ?? $this->callbackUrl;
        $data['invoice_type'] = $payerChoice ? 'gateway_selection' : 'direct_gateway';
        $endpoint = $payerChoice ? 'fiat/pay-request-selection' : 'fiat/pay-request-direct';

        return $this->request($endpoint, 'POST', $data)->getResponse();
    }

    // ---------------------------------------------------------------------
    // Terminal & catalog (read-only)
    // ---------------------------------------------------------------------

    /** GET /terminals/v1/info */
    public function getInformation(): array
    {
        return $this->request('info', 'GET')->getResponse();
    }

    /** GET /terminals/v1/currencies */
    public function getCurrencies(?int $page = null, ?int $perPage = null): array
    {
        return $this->request('currencies', 'GET', ['page' => $page, 'per_page' => $perPage])->getResponse();
    }

    /** GET /terminals/v1/currencies/supported */
    public function getSupportedCurrencies(?int $page = null, ?int $perPage = null): array
    {
        return $this->request('currencies/supported', 'GET', ['page' => $page, 'per_page' => $perPage])->getResponse();
    }

    /** GET /terminals/v1/fiat/gateways */
    public function getFiatGateways(): array
    {
        return $this->request('fiat/gateways', 'GET')->getResponse();
    }

    /** GET /terminals/v1/fiat/currencies */
    public function getFiatCurrencies(): array
    {
        return $this->request('fiat/currencies', 'GET')->getResponse();
    }

    // ---------------------------------------------------------------------
    // Invoice lookup (read-only)
    // ---------------------------------------------------------------------

    /** GET /terminals/v1/invoices?invoice_number=... */
    public function getCryptoInvoice(string $invoiceNumber): array
    {
        return $this->request('invoices', 'GET', ['invoice_number' => $invoiceNumber])->getResponse();
    }

    /** GET /terminals/v1/invoices */
    public function listCryptoInvoices(array $params = []): array
    {
        return $this->request('invoices', 'GET', $params)->getResponse();
    }

    /** GET /terminals/v1/pay-requests */
    public function listPayRequests(array $params = []): array
    {
        return $this->request('pay-requests', 'GET', $params)->getResponse();
    }

    /** GET /terminals/v1/fiat/invoices?invoice_number=... */
    public function getFiatInvoice(string $invoiceNumber): array
    {
        return $this->request('fiat/invoices', 'GET', ['invoice_number' => $invoiceNumber])->getResponse();
    }

    /** GET /terminals/v1/fiat/invoices */
    public function listFiatInvoices(array $params = []): array
    {
        return $this->request('fiat/invoices', 'GET', $params)->getResponse();
    }

    // ---------------------------------------------------------------------
    // Payer-facing actions on hosted invoices (no Token header)
    // ---------------------------------------------------------------------

    /** GET /terminals/v1/fiat/invoices/{invoiceNumber}/gateways */
    public function listFiatInvoiceGateways(string $invoiceNumber): array
    {
        return $this->request(
            'fiat/invoices/' . urlencode($invoiceNumber) . '/gateways',
            'GET',
            [],
            public: true,
        )->getResponse();
    }

    /** POST /terminals/v1/fiat/invoices/{invoiceNumber}/select-gateway */
    public function selectFiatGateway(string $invoiceNumber, array $data): array
    {
        return $this->request(
            'fiat/invoices/' . urlencode($invoiceNumber) . '/select-gateway',
            'POST',
            $data,
            public: true,
        )->getResponse();
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    private function request(string $relativeUrl, string $method, array $payload = [], bool $public = false): self
    {
        $method = strtoupper($method);
        $client = $public ? $this->publicClient : $this->client;

        $options = [];
        if ($method === 'GET') {
            $options['query'] = array_filter($payload, static fn ($v) => $v !== null);
        } else {
            $options['json'] = array_filter($payload, static fn ($v) => $v !== null);
        }

        $this->response = $client->request($method, $relativeUrl, $options);

        $endpoint = strtok($relativeUrl, '?');
        Logger::query()->firstOrCreate(['endpoint' => $endpoint])->increment('count');

        return $this;
    }

    private function getResponse(): array
    {
        return json_decode((string) $this->response->getBody(), true) ?? [];
    }
}
