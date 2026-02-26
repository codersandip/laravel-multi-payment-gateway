<?php

namespace VendorName\MultiPayment\Tests\Feature;

use VendorName\MultiPayment\Tests\TestCase;
use VendorName\MultiPayment\Facades\MultiPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use VendorName\MultiPayment\Events\PaymentSuccess;

class RazorpayDriverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config()->set('multi-payment.gateways.razorpay.key_id', 'test_key');
        config()->set('multi-payment.gateways.razorpay.key_secret', 'test_secret');
    }

    public function test_it_can_create_a_charge()
    {
        Event::fake();

        Http::fake([
            'api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_12345',
                'status' => 'created',
                'amount' => 50000,
            ], 200)
        ]);

        $response = MultiPayment::charge([
            'amount' => 500,
            'currency' => 'INR',
        ]);

        $this->assertTrue($response['success']);
        $this->assertEquals('razorpay', $response['gateway']);
        $this->assertEquals('order_12345', $response['transaction_id']);

        Event::assertDispatched(PaymentSuccess::class);
    }
}
