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
    final public const VERSION = '1.0.0';

    /**
     * Issue API Key from your rublex payments Dashboard
     * @var string
     */
    protected string $apiKey;

    /**
     * Instance of http Client
     * @var Client
     */
    protected Client $client;

    /**
     *  Response from requests made to rublex payments
     * @var mixed
     */
    protected mixed $response;

    /**
     * RublexPayments API base Url
     * @var string
     */
    protected string $baseUrl;

    /**
     * Your callback Url. You can set this in your .env, or you can add
     * it to the $data in the methods that require that option.
     * @var string
     */
    protected string $callbackUrl;


    public function __construct()
    {
        $this->setKey();
        $this->setBaseUrl();
        $this->setRequestOptions();

//        $this->checkStatus();
    }

    /**
     * Get api key from Rublex Payment config file
     */
    public function setKey(): void
    {
        $this->apiKey = Config::get('rublex_payments.apiKey');
    }

    /**
     * Get Base Url from Rublex Payment config file
     */
    public function setBaseUrl(): void
    {
        $this->baseUrl = Config::get('rublex_payments.liveUrl');
        $this->callbackUrl = Config::get('rublex_payments.callbackUrl');
    }

    /**
     * Set options for making the Client request
     */
    private function setRequestOptions(): void
    {
        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Token' => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    /**
     * @return void
     * @throws IsNullException
     */
    public function checkStatus(): void
    {
        $this->setRequestOptions();

        $status = $this->setHttpResponse("/status", 'GET', [])->getResponse();

        if ($status['message'] != "OK") {
            throw new IsNullException("The API is currently not available");
        }

    }

    /**
     * Get the whole response from a get operation
     * @return array
     */
    private function getResponse(): array
    {
        return json_decode($this->response->getBody(), true);
    }

    /**
     * @param string $relativeUrl
     * @param string $method
     * @param array $body
     * @return RublexPayments
     * @throws IsNullException
     */
    private function setHttpResponse(string $relativeUrl, string $method, array $body = []): RublexPayments
    {
        $this->response = $this->client->{strtolower($method)}(
            $this->baseUrl . $relativeUrl,
            ["body" => json_encode($body)]
        );

        if (strlen(stristr($relativeUrl, "?", true)) > 1)
            $relativeUrl = stristr($relativeUrl, "?", true);

        if ($relativeUrl != '/status')
            Logger::query()->updateOrCreate(['endpoint' => $relativeUrl])->increment('count');

        return $this;
    }

    /**
     * @return array
     * @throws IsNullException
     */
    public function getInformation(): array
    {
        return $this->setHttpResponse('info', 'GET')->getResponse();
    }

    /**
     * @param ?int $page
     * @return array
     * @throws IsNullException
     */
    public function getCurrencies(?int $page = null): array
    {
        return $this->setHttpResponse("/currencies", 'GET', compact('page'))->getResponse();
    }

    /**
     * @param ?int $page
     * @param int $per_page
     * @return array
     * @throws IsNullException
     */
    public function getSupportedCurrencies(?int $page = null, int $per_page = 15): array
    {
        return $this->setHttpResponse("/currencies/supported", 'GET', compact('page', 'per_page'))->getResponse();
    }


    /**
     * @param array|null $data
     * @return array
     * @throws IsNullException
     */

    public function createInvoicePayment(array $data = null): array
    {
        if ($data == null)
            $data = [
                'amount' => request()->amount ?? 1,
                'currency_id' => request()->currency_id ?? Currency::BTC,
                'callback_url' => request()->callback_url ?? null,
            ];

        return $this->setHttpResponse('/pay-request', 'POST', array_filter($data))->getResponse();
    }

    /**
     * @param string $invoiceID
     * @return array
     * @throws IsNullException
     */
    public function getInvoicePayment(string $invoiceID): array
    {
        return $this->setHttpResponse('/invoices', 'GET', ['invoice_number' => $invoiceID])->getResponse();
    }

    /**
     * @param array $params
     * @return array
     * @throws IsNullException
     */
    public function getListPayments(array $params = []): array
    {
        return $this->setHttpResponse('/pay-requests', 'GET', $params)->getResponse();
    }

    /**
     * Checks IPN received from rublex payments IPN
     * @return bool
     */
    public function verifyIPN(): bool
    {
        return config('rublex_payments.ipnSecret') == request()->callback_key;
    }

}
