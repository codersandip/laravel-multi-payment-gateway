<?php

namespace VendorName\MultiPayment;

use Illuminate\Support\Manager;
use Illuminate\Support\Facades\Log;
use VendorName\MultiPayment\Contracts\PaymentContract;
use VendorName\MultiPayment\Drivers\RazorpayDriver;
use VendorName\MultiPayment\Drivers\PayUDriver;
use VendorName\MultiPayment\Drivers\StripeDriver;
use VendorName\MultiPayment\Drivers\CashfreeDriver;
use VendorName\MultiPayment\Exceptions\PaymentGatewayException;
use VendorName\MultiPayment\Events\PaymentSuccess;
use VendorName\MultiPayment\Events\PaymentFailed;
use VendorName\MultiPayment\Jobs\ProcessAsyncPayment;
use VendorName\MultiPayment\DTOs\ChargeData;
use VendorName\MultiPayment\Models\PaymentTransaction;
use Exception;

class PaymentManager extends Manager implements PaymentContract
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('multi-payment.default', 'razorpay');
    }

    public function createRazorpayDriver(): RazorpayDriver
    {
        return new RazorpayDriver($this->config->get('multi-payment.gateways.razorpay', []));
    }

    public function createPayuDriver(): PayUDriver
    {
        return new PayUDriver($this->config->get('multi-payment.gateways.payu', []));
    }

    public function createStripeDriver(): StripeDriver
    {
        return new StripeDriver($this->config->get('multi-payment.gateways.stripe', []));
    }

    public function createCashfreeDriver(): CashfreeDriver
    {
        return new CashfreeDriver($this->config->get('multi-payment.gateways.cashfree', []));
    }

    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }

    public function charge(array|ChargeData $data): array
    {
        if (is_array($data)) {
            $data = ChargeData::fromArray($data);
        }

        return $this->executeWithFailover('charge', [$data]);
    }

    /**
     * Dispatch an asynchronous charge job
     */
    public function chargeAsync(array|ChargeData $data)
    {
        if (is_array($data)) {
            $data = ChargeData::fromArray($data);
        }

        ProcessAsyncPayment::dispatch('charge', [$data]);
    }

    public function refund(string $transactionId, float $amount): array
    {
        return $this->executeWithFailover('refund', [$transactionId, $amount]);
    }

    public function verify(array $payload): array
    {
        return $this->executeWithFailover('verify', [$payload]);
    }

    public function handleWebhook(array $payload, string $signature): array
    {
        return $this->executeWithFailover('handleWebhook', [$payload, $signature]);
    }

    /**
     * Execute a method with automatic failover, retries, logging, and events.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws PaymentGatewayException
     */
    protected function executeWithFailover(string $method, array $parameters)
    {
        $drivers = array_merge([$this->getDefaultDriver()], $this->config->get('multi-payment.failovers', []));
        $lastException = null;

        $retries = $this->config->get('multi-payment.retries.attempts', 2);
        $sleepMs = $this->config->get('multi-payment.retries.sleep', 1000);
        $logChannel = $this->config->get('multi-payment.logging.channel', 'daily');

        // Pre-create DB transaction if it's a charge method
        $dbTransaction = null;
        if ($method === 'charge' && isset($parameters[0]) && $parameters[0] instanceof ChargeData) {
            $chargeInfo = $parameters[0];
            $dbTransaction = PaymentTransaction::create([
                'gateway' => $this->getDefaultDriver(),
                'receipt_id' => $chargeInfo->receiptId,
                'amount' => $chargeInfo->amount,
                'currency' => $chargeInfo->currency->value,
                'customer_email' => $chargeInfo->email,
                'status' => 'pending',
                'metadata' => $chargeInfo->metadata,
            ]);
        }

        foreach ($drivers as $driverName) {
            try {
                // Update gateway attempt in DB
                if ($dbTransaction) {
                    $dbTransaction->update(['gateway' => $driverName]);
                }

                $driver = $this->driver($driverName);
                
                // Native Laravel retry helper implementation
                $response = retry($retries, function () use ($driver, $method, $parameters) {
                    return $driver->$method(...$parameters);
                }, $sleepMs);

                // Update DB transaction on success
                if ($dbTransaction) {
                    $dbTransaction->update([
                        'transaction_id' => $response['transaction_id'] ?? null,
                        'status' => $response['status'] ?? 'captured',
                        'raw_response' => $response['raw'] ?? []
                    ]);
                }

                // Dispatch Success Event
                event(new PaymentSuccess($response));
                
                Log::channel($logChannel)->info("Payment [{$driverName}] {$method} successful.");
                
                return $response;

            } catch (Exception $e) {
                // Dispatch Failed Event
                event(new PaymentFailed($e, $driverName));
                
                Log::channel($logChannel)->error("Payment [{$driverName}] {$method} failed: " . $e->getMessage());
                
                if ($dbTransaction) {
                    $dbTransaction->update([
                        'status' => 'failed',
                        'raw_response' => ['error' => $e->getMessage()]
                    ]);
                }

                $lastException = new PaymentGatewayException(
                    "Gateway [{$driverName}] failed: " . $e->getMessage(),
                    $driverName,
                    $e->getCode(),
                    $e
                );
            }
        }

        Log::channel($logChannel)->critical("All registered payment gateways failed for method {$method}.");
        throw $lastException ?? new PaymentGatewayException("All registered payment gateways failed.");
    }
}
