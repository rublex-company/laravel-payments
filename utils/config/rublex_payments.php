<?php

/*
 * This file is part of the Laravel Rublex Payments package.
 *
 * (c) Rublex Team <payments@rublex.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


return [

    /**
     * API Key From Rublex Payments Dashboard
     *
     */
    'apiKey' => env('RUBLEX_PAYMENTS_API_KEY'),

    /**
     * IPN Secret (Local Secret Key)
     */
    'ipnSecret' => env('RUBLEX_PAYMENTS_IPN_SECRET'),

    /**
     * Rublex Payments Live URL
     *
     */
    'liveUrl' => env('RUBLEX_PAYMENTS_URL', "https://panel.pay.rublex.io/terminals/v1/"),

    /**
     * Your callback URL
     *
     */
    'callbackUrl' => env('RUBLEX_PAYMENTS_CALLBACK_URL'),

    /**
     * Your URL Path
     *
     */
    'path' => 'rublex-payments',

    /**
     * You can add your custom middleware to access the dashboard here
     *
     */
    'middleware' => null, // "Authorise::class",

];