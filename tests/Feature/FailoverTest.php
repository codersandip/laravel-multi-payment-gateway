<?php

namespace VendorName\MultiPayment\Tests\Feature;

use VendorName\MultiPayment\Tests\TestCase;
use VendorName\MultiPayment\Facades\MultiPayment;
use Illuminate\Support\Facades\Http;
use VendorName\MultiPayment\Exceptions\PaymentGatewayException;

class FailoverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        
        config()->set('multi-payment.default', 'razorpay');
        config()->set('multi-payment.failovers', ['stripe']);
        
        config()->set('multi-payment.gateways.razorpay.key_id', 'test');
        config()->set('multi-payment.gateways.stripe.secret_key', 'test');
    }

    public function test_it_switches_to_failover_when_primary_fails()
    {
        Http::fake([
            'api.razorpay.com/v1/orders' => Http::response(['error' => ['description' => 'Failed']], 400),
            'api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_123',
                'payment_status' => 'paid',
                'url' => 'https://checkout.stripe.com/pay/cs_123'
            ], 200)
        ]);

        $response = MultiPayment::charge([
            'amount' => 500,
            'currency' => 'INR',
        ]);

        // It should have failed Razorpay and successfully used Stripe
        $this->assertTrue($response['success']);
        $this->assertEquals('stripe', $response['gateway']);
        $this->assertEquals('cs_123', $response['transaction_id']);
    }
}
