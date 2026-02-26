<?php

namespace VendorName\MultiPayment\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use VendorName\MultiPayment\MultiPaymentServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;
    protected function getPackageProviders($app)
    {
        return [
            MultiPaymentServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('multi-payment.default', 'razorpay');
        $app['config']->set('multi-payment.failovers', []);
        $app['config']->set('multi-payment.logging.channel', 'null');
        $app['config']->set('multi-payment.retries.attempts', 1); // Disable retries for testing
        
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
