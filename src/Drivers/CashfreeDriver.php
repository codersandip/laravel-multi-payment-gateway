<?php

namespace VendorName\MultiPayment\Drivers;

use Illuminate\Support\Facades\Http;
use VendorName\MultiPayment\Contracts\PaymentContract;
use VendorName\MultiPayment\Traits\FormatsResponse;
use VendorName\MultiPayment\Exceptions\PaymentGatewayException;
use VendorName\MultiPayment\DTOs\ChargeData;

class CashfreeDriver implements PaymentContract
{
    use FormatsResponse;

    protected $config;
    protected $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = ($config['test_mode'] ?? true)
            ? 'https://sandbox.cashfree.com/pg'
            : 'https://api.cashfree.com/pg';
    }

    protected function headers()
    {
        return [
            'x-client-id' => $this->config['app_id'] ?? '',
            'x-client-secret' => $this->config['secret_key'] ?? '',
            'x-api-version' => '2023-08-01',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    public function charge(array|ChargeData $data): array
    {
        $data = is_array($data) ? ChargeData::fromArray($data) : $data;

        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl . '/orders', [
                'order_amount'   => $data->amount,
                'order_currency' => $data->currency->value,
                'customer_details' => [
                    'customer_id' => $data->receiptId,
                    'customer_email' => $data->email ?? 'test@cashfree.com',
                    'customer_phone' => $data->phone ?? '9999999999',
                ]
            ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Cashfree Charge Failed: ' . $response->json('message', 'Unknown error'), 'cashfree');
        }

        $result = $response->json();

        return $this->formatResponse(
            true,
            'cashfree',
            $result['order_id'] ?? null,
            $result['order_status'] ?? 'pending',
            'Order created',
            $result
        );
    }

    public function refund(string $transactionId, float $amount): array
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl . "/orders/{$transactionId}/refunds", [
                'refund_amount' => $amount,
                'refund_id' => 'refund_' . uniqid(),
            ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Cashfree Refund Failed: ' . $response->json('message', 'Unknown error'), 'cashfree');
        }

        $result = $response->json();

        return $this->formatResponse(true, 'cashfree', $result['refund_id'] ?? null, 'refunded', 'Refund successful', $result);
    }

    public function verify(array $payload): array
    {
        $orderId = $payload['order_id'] ?? null;
        if (!$orderId) {
            throw new PaymentGatewayException('Missing order_id for Cashfree verification', 'cashfree');
        }

        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl . "/orders/{$orderId}");

        if ($response->failed()) {
            throw new PaymentGatewayException('Cashfree Verification Failed', 'cashfree');
        }

        $result = $response->json();

        return $this->formatResponse($result['order_status'] === 'PAID', 'cashfree', $orderId, $result['order_status'], 'Verification complete', $result);
    }

    public function handleWebhook(array $payload, string $signature): array
    {
        $timestamp = rtrim($payload['timestamp'] ?? '');
        $rawBody = rtrim($payload['rawBody'] ?? '');
        
        $computedSignature = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, $this->config['secret_key'] ?? '', true));

        if (!hash_equals($computedSignature, $signature)) {
            throw new PaymentGatewayException('Invalid Cashfree Webhook Signature', 'cashfree');
        }

        return $this->formatResponse(true, 'cashfree', $payload['data']['order']['order_id'] ?? null, 'webhook_received', 'Webhook verified', $payload);
    }
}
