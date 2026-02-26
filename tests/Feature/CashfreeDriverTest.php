<?php

namespace VendorName\MultiPayment\Tests\Feature;

use VendorName\MultiPayment\Tests\TestCase;
use VendorName\MultiPayment\Facades\MultiPayment;
use Illuminate\Support\Facades\Http;

class CashfreeDriverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config()->set('multi-payment.gateways.cashfree.app_id', 'test_app');
        config()->set('multi-payment.gateways.cashfree.secret_key', 'test_secret');
        config()->set('multi-payment.gateways.cashfree.test_mode', true);
    }

    public function test_it_can_create_cashfree_order()
    {
        Http::fake([
            'sandbox.cashfree.com/pg/orders' => Http::response([
                'order_id' => 'order_CF_1234',
                'order_status' => 'ACTIVE',
            ], 200)
        ]);

        $response = MultiPayment::driver('cashfree')->charge([
            'amount' => 500,
            'currency' => 'INR',
        ]);

        $this->assertTrue($response['success']);
        $this->assertEquals('cashfree', $response['gateway']);
        $this->assertEquals('order_CF_1234', $response['transaction_id']);
    }
}
