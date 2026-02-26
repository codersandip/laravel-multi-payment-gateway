# Laravel Multi-Payment Gateway Wrapper

A unified, production-ready payment gateway package for Indian-focused gateways (Razorpay, PayU, Stripe, Cashfree). Built specifically for Laravel 10+, implementing clean architecture, SOLID principles, and an automatic driver failover mechanism.

## Features
- **Driver-based Architecture:** Clean abstractions over multiple gateways.
- **Database Tracking:** Eloquent models log all transactions, allowing instant audit trails.
- **Failover & Retries:** Automatic retries for failed requests, and fallback to secondary gateways.
- **Robust Event System:** Automatically dispatches `PaymentSuccess` and `PaymentFailed` events.
- **Async Queue Jobs:** Run payment actions in the background.
- **Auto-Reconciliation Artisan Command:** Scheduled DB cleanup for pending webhooks.
- **Extensible:** Easily register custom drivers.
- **Centralized Logging:** Dedicated channel logging for all gateway interactions.
- **Unified Standard Response:** Predictable array returns `[success, gateway, transaction_id, status, message, raw]`.
- **No Third-Party SDKs:** Powered completely by Laravel's built-in HTTP client (`Illuminate\Support\Facades\Http`).

## Installation

You can install the package via Composer:

```bash
composer require vendorname/laravel-multi-payment-gateway
```

Publish the configuration file and migrate the database:

```bash
php artisan vendor:publish --provider="VendorName\MultiPayment\MultiPaymentServiceProvider" --tag="config"
php artisan migrate
```

## Configuration

In `config/multi-payment.php`, you can set the default driver, failover drivers, error logging channel, and the allowed retries before moving to a fallback.

```php
'default' => 'razorpay',
'failovers' => ['stripe', 'cashfree'],
'retries' => [
    'attempts' => 2,
    'sleep' => 1000, // milliseconds
],
```

## Usage

### 1. Basic Charge

```php
use VendorName\MultiPayment\Facades\MultiPayment;

$response = MultiPayment::charge([
    'amount' => 500,
    'currency' => 'INR',
    'email' => 'customer@test.com',
    'phone' => '9999999999',
]);
```

### 2. Async Background Process

If you wish to handle the request later in the background through the Laravel Queue, simply use:

```php
MultiPayment::chargeAsync([
    'amount' => 500,
    'currency' => 'INR',
]);
```

### 3. Failover Execution

The manager automatically attempts the charge via the default gateway using Laravel's native `retry()` mechanism. If all retries exhaust, the manager catches the Exception and automatically shifts to the failover stack (e.g. from Razorpay directly to Stripe).

### 4. Events System

The package fires standard events whenever a gateway successfully completes an API execution or exhausts all retries resulting in an error.

To listen to these events, register listeners in `EventServiceProvider`:

```php
use VendorName\MultiPayment\Events\PaymentSuccess;
use VendorName\MultiPayment\Events\PaymentFailed;

protected $listen = [
    PaymentSuccess::class => [
        SendPaymentReceipt::class,
    ],
    PaymentFailed::class => [
        AlertSupportTeam::class,
    ],
];
```

### 5. Custom Driver Extensibility

Thanks to Laravel's Manager pattern, extending this package with any custom gateway is trivial. Inside your `AppServiceProvider` boot method:

```php
use VendorName\MultiPayment\Facades\MultiPayment;
use App\Payment\CustomGatewayDriver;

MultiPayment::extend('custom_gateway', function ($app) {
    return new CustomGatewayDriver(config('services.custom'));
});
```
Then use it instantly: `MultiPayment::driver('custom_gateway')->charge(...)`

## Webhooks

We provide a built-in macro to handle webhooks asynchronously across any enabled gateway. 
Register this in your `routes/api.php`:

```php
Route::paymentWebhooks('webhooks/payments');
```
This automatically handles verified posts to `/api/webhooks/payments/razorpay`, `.../stripe`, etc.

## Database & Auto Reconciliation

Every `charge` request securely logs an `pending` Eloquent `PaymentTransaction` before communicating with the Gateway. Once a gateway returns successfully (or fails across all failovers), the DB is synced seamlessly.

If a Sandbox Sandbox or webhooks drop instantly, you can run and schedule our artisan command to manually query the Gateways and fix all drifted `pending` payments:

```bash
php artisan payment:reconcile-pending --days=3
```

You can cleanly schedule this in Laravel's `Console/Kernel.php`:

```php
$schedule->command('payment:reconcile-pending')->hourly();
```

## Frontend / Blade Components

The package natively ships with beautiful, pre-configured Blade Components targeting the official JavaScript SDK/Widgets of every supported gateway!

You can easily publish these views to customize them locally:

```bash
php artisan vendor:publish --tag="multi-payment-views"
```

Once a `charge` executes, pass the payload `$response` to any component in your blade file. They automatically mount the UI and redirect to your specified `$verifyUrl` upon success!

```blade
{{-- Stripe Elements --}}
@include('multi-payment::components.stripe', [
    'response' => $chargeResponse,
    'verifyUrl' => route('payment.verify') 
])

{{-- Razorpay Checkout js Modal --}}
@include('multi-payment::components.razorpay', [
    'response' => $chargeResponse,
    'verifyUrl' => route('payment.verify'),
    'themeColor' => '#ff0000', // Optional
])

{{-- Cashfree SDK Modal --}}
@include('multi-payment::components.cashfree', [
    'response' => $chargeResponse
])

{{-- PayU Auto-Submit Form --}}
@include('multi-payment::components.payu', [
    'response' => $chargeResponse,
    'autoOpen' => true 
])
```

## Testing

The package relies on `orchestra/testbench` for Laravel container bindings. Run tests natively via PHPUnit:

```bash
composer require --dev orchestra/testbench phpunit/phpunit
vendor/bin/phpunit
```
