<?php

if (!function_exists("rublex_payments")) {
    function rublex_payments()
    {

        return app()->make('laravel-rublex-payments');
    }
}