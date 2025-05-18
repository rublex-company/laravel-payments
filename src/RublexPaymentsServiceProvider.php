<?php

namespace Rublex\Payments;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/*
 * This file is part of the Laravel Rublex Payments package.
 *
 * (c) Rublex Team <payments@rublex.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class RublexPaymentsServiceProvider extends ServiceProvider
{

    /*
    * Indicates if loading of the provider is deferred.
    *
    * @var bool
    */
    protected $defer = false;

    /**
     * Publishes all the config file this package needs to function
     */
    public function boot()
    {
        $config = realpath(__DIR__ . '/../utils/config/rublex_payments.php');

        $this->publishes([
            $config => config_path('rublex_payments.php')
        ], 'config');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->mergeConfigFrom(
            __DIR__ . '/../utils/config/rublex_payments.php', 'rublex_payments'
        );
        if (File::exists(__DIR__ . '/../utils/helpers/rublex_payments.php')) {
            require __DIR__ . '/../utils/helpers/rublex_payments.php';
        }

        $this->registerDashboard();

    }

    /**
     * Register the dashboard components.
     *
     * @return void
     */
    protected function registerDashboard(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/routes.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views/', 'rublex_payments');
        $this->registerDashboardGate();
    }

    /**
     * Register the dashboard gate.
     *
     * @return void
     */
    protected function registerDashboardGate(): void
    {
        Gate::define('viewRublexPaymentsDashboard', function ($user = null) {
            return $this->app->environment('local');
        });
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->app->bind('laravel-rublex-payments', function () {
            return new RublexPayments();
        });
    }

    /**
     * Get the services provided by the provider
     * @return array
     */
    public function provides(): array
    {
        return ['laravel-rublex-payments'];
    }

}