<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; //add fixed sql


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //fix db sql
        Schema::defaultStringLength(191); //add fixed sql
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //

        //验证手机号
        \Validator::extend('phone', function ($attribute, $value, $parameters, $validator) {
            $pattern = '/^1[3456789]{1}\d{9}$/';
            $res = preg_match($pattern, $value);

            return $res > 0;
        });
        \Validator::replacer('phone', function ($message, $attribute, $rule, $parameters) {
            return $message;
            //return str_replace($attribute,$rule,$message);
        });
    }
}
