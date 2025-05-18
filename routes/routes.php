<?php

use Illuminate\Support\Facades\Route;
use Rublex\Payments\Http\Controllers\DashboardController;
use Rublex\Payments\Http\Middleware\Authorize;

/*
 * This file is part of the Laravel Rublex Payments package.
 *
 * (c) Rublex Team <payments@rublex.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

Route::group([
    'prefix' => config('rublex_payments.path', 'laravel-rublex-payments'),
    'middleware' => config('rublex_payments.middleware', [Authorize::class]),
], function () {
    Route::get('/', DashboardController::class)->name('rublex.payments.dashboard');
});
