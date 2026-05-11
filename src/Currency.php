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
 * Currency IDs are dynamic and terminal-scoped. Fetch them at runtime from
 * GET /terminals/v1/currencies/supported (RublexPayments::getSupportedCurrencies)
 * rather than relying on hard-coded constants.
 */

class Currency
{
}
