<?php

namespace Rublex\Payments;

/*
 * This file is part of the Laravel Rublex Payments package.
 *
 * (c) Rublex Team <payments@rublex.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Fluent builder for invoice creation.
 *
 * Entry points : RublexPayments::crypto() · RublexPayments::fiat()
 * Direction    : pick($id) → merchant locks coin/gateway
 *                byPayer() → payer picks it at checkout instead
 * Rate (fiat)  : lockRate() / lockRate(false)
 * Customer     : customer(email:, firstName:, lastName:, mobile:)
 * Execute      : createInvoice([extras])
 */

class InvoiceBuilder
{
    public const TYPE_CRYPTO = 'crypto';
    public const TYPE_FIAT = 'fiat';

    protected array $data = [];

    protected bool $payerChoice = false;

    public function __construct(
        protected RublexPayments $client,
        protected string $type,
    ) {
        if ($type === self::TYPE_FIAT) {
            $this->data['fixed_rate'] = true;
        }
    }

    public function amount(float|int $amount): self
    {
        $this->data['amount'] = $amount;
        return $this;
    }

    /**
     * Pick the coin (crypto) or the gateway (fiat) that this invoice locks to.
     * Still useful in byPayer() mode for crypto — it then represents the payout
     * coin — and acts as the default suggestion for fiat.
     */
    public function pick(int $id): self
    {
        $key = $this->type === self::TYPE_CRYPTO ? 'currency_id' : 'gateway_id';
        $this->data[$key] = $id;
        return $this;
    }

    /**
     * Let the payer choose the coin (crypto) or gateway (fiat) at checkout
     * instead of locking it on the merchant side.
     */
    public function byPayer(): self
    {
        $this->payerChoice = true;
        return $this;
    }

    public function by_payer(): self
    {
        return $this->byPayer();
    }

    public function callback(?string $url): self
    {
        $this->data['callback_url'] = $url;
        return $this;
    }

    /**
     * Fiat-only: control the FX rate behaviour.
     *
     *   lockRate()       → lock the rate at invoice creation time
     *   lockRate(false)  → let the rate float until the payer pays
     */
    public function lockRate(bool $lock = true): self
    {
        $this->data['fixed_rate'] = $lock;
        return $this;
    }

    /**
     * Fiat-only: pre-fill payer details. All four fields are optional;
     * pass via named arguments — e.g. ->customer(email: 'a@b.com').
     */
    public function customer(
        ?string $email = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $mobile = null,
    ): self {
        if ($email !== null) $this->data['customer_email'] = $email;
        if ($firstName !== null) $this->data['customer_first_name'] = $firstName;
        if ($lastName !== null) $this->data['customer_last_name'] = $lastName;
        if ($mobile !== null) $this->data['customer_mobile'] = $mobile;
        return $this;
    }

    /**
     * @throws IsNullException
     */
    public function createInvoice(array $extras = []): array
    {
        $data = array_merge($this->data, $extras);

        return $this->type === self::TYPE_CRYPTO
            ? $this->client->createCryptoInvoice($data, $this->payerChoice)
            : $this->client->createFiatInvoice($data, $this->payerChoice);
    }
}
