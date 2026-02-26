<?php

namespace VendorName\MultiPayment\Drivers;

use Illuminate\Support\Facades\Http;
use VendorName\MultiPayment\Contracts\PaymentContract;
use VendorName\MultiPayment\Traits\FormatsResponse;
use VendorName\MultiPayment\Exceptions\PaymentGatewayException;
use VendorName\MultiPayment\DTOs\ChargeData;

class StripeDriver implements PaymentContract
{
    use FormatsResponse;

    protected $config;
    protected $baseUrl = 'https://api.stripe.com/v1/';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function charge(array|ChargeData $data): array
    {
        $data = is_array($data) ? ChargeData::fromArray($data) : $data;

        // Use Stripe Checkout Sessions for hosted redirection
        $response = Http::withToken($this->config['secret_key'] ?? '')
            ->asForm()
            ->post($this->baseUrl . 'checkout/sessions', [
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower($data->currency->value),
                            'product_data' => [
                                'name' => $data->description ?? 'Order Payment',
                            ],
                            'unit_amount' => $data->amount * 100, // in cents
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => url('/checkout?status=success&session_id={CHECKOUT_SESSION_ID}'),
                'cancel_url' => url('/checkout?status=cancelled'),
                'customer_email' => $data->email,
                'client_reference_id' => $data->receiptId,
            ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Stripe Checkout Session Failed: ' . $response->json('error.message', 'Unknown error'), 'stripe');
        }

        $result = $response->json();

        return $this->formatResponse(
            true,
            'stripe',
            $result['id'] ?? null,
            trim(strtolower($result['payment_status'] ?? 'pending')),
            'Checkout session created successfully',
            $result // $result['url'] contains the redirect URL
        );
    }

    public function refund(string $transactionId, float $amount): array
    {
        $response = Http::withToken($this->config['secret_key'] ?? '')
            ->asForm()
            ->post($this->baseUrl . 'refunds', [
                'payment_intent' => $transactionId,
                'amount' => $amount * 100,
            ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('Stripe Refund Failed: ' . $response->json('error.message', 'Unknown error'), 'stripe');
        }

        $result = $response->json();

        return $this->formatResponse(true, 'stripe', $result['id'] ?? null, 'refunded', 'Refund successful', $result);
    }

    public function verify(array $payload): array
    {
        $transactionId = $payload['payment_intent'] ?? null;
        if (!$transactionId) {
            throw new PaymentGatewayException('Missing payment_intent for verification', 'stripe');
        }

        $response = Http::withToken($this->config['secret_key'] ?? '')
            ->get($this->baseUrl . "payment_intents/{$transactionId}");

        if ($response->failed()) {
            throw new PaymentGatewayException('Stripe Verification Failed', 'stripe');
        }

        $result = $response->json();
        
        return $this->formatResponse(
            $result['status'] === 'succeeded',
            'stripe',
            $transactionId,
            $result['status'],
            'Verification complete',
            $result
        );
    }

    public function handleWebhook(array $payload, string $signature): array
    {
        if (empty($signature)) {
            throw new PaymentGatewayException('Missing Webhook Signature', 'stripe');
        }

        return $this->formatResponse(true, 'stripe', $payload['data']['object']['id'] ?? null, 'webhook_received', 'Webhook handled', $payload);
    }
}
