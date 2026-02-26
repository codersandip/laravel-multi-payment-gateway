<?php

namespace VendorName\MultiPayment;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class MultiPaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/multi-payment.php', 'multi-payment');

        $this->app->singleton('multi-payment', function ($app) {
            return new PaymentManager($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/multi-payment.php' => config_path('multi-payment.php'),
            ], 'multi-payment-config');

            $this->publishes([
                __DIR__.'/../resources/views/components' => resource_path('views/vendor/multi-payment/components'),
            ], 'multi-payment-views');
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'multi-payment');

        Route::macro('paymentWebhooks', function (string $uri) {
            Route::post("$uri/{gateway}", '\\VendorName\\MultiPayment\\Http\\Controllers\\WebhookController');
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                \VendorName\MultiPayment\Console\Commands\ReconcilePendingPaymentsCommand::class,
            ]);
        }
    }
}
