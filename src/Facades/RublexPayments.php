<?php

namespace Rublex\payments\Facades;

use Illuminate\Support\Facades\Facade;

/*
 * This file is part of the Laravel Rublex Payments package.
 *
 * (c) Rublex Team <payments@rublex.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class RublexPayments extends Facade
{
    final public const VERSION = '1.0.0';

    /**
     * Get the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravel-rublex-payments';
    }

}