<?php

namespace VendorName\MultiPayment\Drivers;

use Illuminate\Support\Facades\Http;
use VendorName\MultiPayment\Contracts\PaymentContract;
use VendorName\MultiPayment\Traits\FormatsResponse;
use VendorName\MultiPayment\Exceptions\PaymentGatewayException;
use VendorName\MultiPayment\DTOs\ChargeData;

class RazorpayDriver implements PaymentContract
{
    use FormatsResponse;

    protected $config;
    protected $baseUrl = 'https://api.razorpay.com/v1/';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function charge(array|ChargeData $data): array
    {
        $data = is_array($data) ? ChargeData::fromArray($data) : $data;

        $response = Http::withBasicAuth($this->config['key_id'] ?? '', $this->config['key_secret'] ?? '')
            ->timeout(15)
            ->post($this->baseUrl . 'orders', [
                // Safely cast to integer and round to avoid float precision loss (e.g. 500.20 * 100)
                'amount'   => (int) round($data->amount * 100), 
                'currency' => $data->currency->value,
                'receipt'  => (string) $data->receiptId,
            ]);

        if ($response->failed()) {
            $error = $response->json('error.description') ?? $response->body();
            $reason = $response->json('error.reason');
            $message = $reason ? "{$error} (Reason: {$reason})" : $error;
            
            throw new PaymentGatewayException('Razorpay Charge Failed: ' . $message, 'razorpay');
        }

        $result = $response->json();

        return $this->formatResponse(
            true,
            'razorpay',
            $result['id'] ?? null,
            trim(strtolower($result['status'] ?? 'pending')),
            'Order created successfully',
            $result
        );
    }

    public function refund(string $transactionId, float $amount): array
    {
        $response = Http::withBasicAuth($this->config['key_id'] ?? '', $this->config['key_secret'] ?? '')
            ->timeout(15)
            ->post($this->baseUrl . "payments/{$transactionId}/refund", [
                'amount' => (int) round($amount * 100),
            ]);

        if ($response->failed()) {
            $error = $response->json('error.description') ?? $response->body();
            throw new PaymentGatewayException('Razorpay Refund Failed: ' . $error, 'razorpay');
        }

        $result = $response->json();

        return $this->formatResponse(
            true,
            'razorpay',
            $result['id'] ?? null,
            'refunded',
            'Refund successful',
            $result
        );
    }

    public function verify(array $payload): array
    {
        $signature = $payload['razorpay_signature'] ?? '';
        $orderId = $payload['razorpay_order_id'] ?? '';
        $paymentId = $payload['razorpay_payment_id'] ?? '';

        $generatedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->config['key_secret'] ?? '');

        if (!empty($signature) && hash_equals($generatedSignature, $signature)) {
            return $this->formatResponse(true, 'razorpay', $paymentId, 'captured', 'Signature verified', $payload);
        }

        throw new PaymentGatewayException('Razorpay verification failed: Signature mismatch', 'razorpay');
    }

    public function handleWebhook(array $payload, string $signature): array
    {
        // Webhook signatures MUST be verified against the exact RAW body string, not an encoded array 
        // to prevent hash mismatches from JSON spacing differences.
        $rawBody = request()->getContent() ?: json_encode($payload);
        $generatedSignature = hash_hmac('sha256', $rawBody, $this->config['webhook_secret'] ?? '');
        
        if (!empty($signature) && hash_equals($generatedSignature, $signature)) {
            return $this->formatResponse(true, 'razorpay', $payload['payload']['payment']['entity']['id'] ?? null, 'webhook_received', 'Webhook verified', $payload);
        }

        throw new PaymentGatewayException('Invalid Webhook Signature', 'razorpay');
    }
}
