<?php

namespace Shengamo\TumenyPay;

use Illuminate\Support\ServiceProvider;
use Shengamo\TumenyPay\Commands\VerifyPayment;

class TumenyPayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        $this->mergeConfigFrom(__DIR__ . '/../config/tumeny.php', 'tumeny');

        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'tumeny-pay');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'tumeny-pay');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/tumeny.php', 'tumeny');

        // Register the main class to use with the facade
        $this->app->singleton('tumeny-pay', function () {
            return new TumenyPay;
        });
    }

    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/tumeny.php' => config_path('tumeny.php'),
        ], 'tumeny-pay.config');

        if (empty(glob(database_path('migrations/*_create_shengamo_orders_table.php')))) {
            $this->publishes([
                __DIR__ . '/../database/migrations/create_shengamo_orders_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_shengamo_orders_table.php'),
                __DIR__ . '/../database/migrations/create_shengamo_order_statuses_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_shengamo_order_statuses_table.php'),
            ], 'migrations');
        }

//        if (empty(glob(database_path('migrations/*_create_team_subscriptions_table.php')))) {
//            $this->publishes([
//                __DIR__.'/../database/migrations/create_team_subscriptions_table.php' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_team_subscriptions_table.php'),
//            ], 'migrations');
//        }

        // Registering package commands.
         $this->commands([
             VerifyPayment::class,
         ]);
    }

}
