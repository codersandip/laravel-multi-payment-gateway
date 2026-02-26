<?php

namespace VendorName\MultiPayment\Tests\Feature;

use VendorName\MultiPayment\Tests\TestCase;
use VendorName\MultiPayment\Facades\MultiPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use VendorName\MultiPayment\Events\PaymentSuccess;

class StripeDriverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config()->set('multi-payment.default', 'stripe');
        config()->set('multi-payment.gateways.stripe.secret_key', 'test_secret');
    }

    public function test_it_can_create_a_stripe_charge()
    {
        Event::fake();

        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test123',
                'payment_status' => 'paid',
                'amount_total' => 50000,
                'url' => 'https://checkout.stripe.com/pay/cs_test123'
            ], 200)
        ]);

        $response = MultiPayment::charge([
            'amount' => 500,
            'currency' => 'usd',
        ]);

        $this->assertTrue($response['success']);
        $this->assertEquals('stripe', $response['gateway']);
        $this->assertEquals('cs_test123', $response['transaction_id']);

        Event::assertDispatched(PaymentSuccess::class);
    }
}
