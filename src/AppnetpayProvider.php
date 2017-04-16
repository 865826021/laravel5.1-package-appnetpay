<?php

namespace Yuxiaoyang\Appnetpay;

use Illuminate\Support\ServiceProvider;

class AppnetpayProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('appnetpay',function(){
            return new Appnetpay();
        });//app('appnetpay')
    }
}
