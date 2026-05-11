<?php

namespace Rublex\Payments\Facades;

use Illuminate\Support\Facades\Facade;

/*
 * This file is part of the Laravel Rublex Payments package.
 *
 * (c) Rublex Team <payments@rublex.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Invoice creation
 * @method static \Rublex\Payments\InvoiceBuilder crypto()
 * @method static \Rublex\Payments\InvoiceBuilder fiat()
 * @method static array createCryptoInvoice(array $data, bool $payerChoice = false)
 * @method static array createFiatInvoice(array $data, bool $payerChoice = false)
 *
 * Terminal & catalog
 * @method static array getInformation()
 * @method static array getCurrencies(?int $page = null, ?int $perPage = null)
 * @method static array getSupportedCurrencies(?int $page = null, ?int $perPage = null)
 * @method static array getFiatGateways()
 * @method static array getFiatCurrencies()
 *
 * Invoice lookup
 * @method static array getCryptoInvoice(string $invoiceNumber)
 * @method static array listCryptoInvoices(array $params = [])
 * @method static array listPayRequests(array $params = [])
 * @method static array getFiatInvoice(string $invoiceNumber)
 * @method static array listFiatInvoices(array $params = [])
 *
 * Payer-facing actions on hosted invoices
 * @method static array selectCryptoCurrency(string $invoiceNumber, int $currencyId)
 * @method static array listFiatInvoiceGateways(string $invoiceNumber)
 * @method static array selectFiatGateway(string $invoiceNumber, array $data)
 */
class RublexPayments extends Facade
{
    final public const VERSION = '1.1.0';

    protected static function getFacadeAccessor(): string
    {
        return 'laravel-rublex-payments';
    }
}
