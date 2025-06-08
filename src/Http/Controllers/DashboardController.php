<?php

namespace Rublex\Payments\Http\Controllers;

use Illuminate\Http\Request;
use Rublex\Payments\Facades\RublexPayments;
use Rublex\Payments\Models\Logger;

/*
 * This file is part of the Laravel Rublex Payments package.
 *
 * (c) Rublex Team <payments@rublex.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class DashboardController
{
    public function index(Request $request)
    {
        $logs = Logger::get();
        $version = RublexPayments::VERSION;
        return view('rublex_payments::dashboard', compact('logs', 'version'));
    }
}
