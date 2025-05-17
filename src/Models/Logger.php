<?php

namespace Rublex\payments\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/*
 * This file is part of the Laravel Rublex Payments package.
 *
 * (c) Rublex Team <payments@rublex.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Logger extends Model
{
    public $timestamps = false;
    protected $fillable = ['endpoint', 'count'];
    protected $table = 'rublex_payments_api_call_logger';
}
