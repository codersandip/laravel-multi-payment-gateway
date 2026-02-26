<?php

namespace VendorName\MultiPayment\Tests\Feature;

use VendorName\MultiPayment\Tests\TestCase;
use VendorName\MultiPayment\Facades\MultiPayment;
use Illuminate\Support\Facades\Http;

class PayUDriverTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config()->set('multi-payment.gateways.payu.merchant_key', 'test_key');
        config()->set('multi-payment.gateways.payu.salt', 'test_salt');
    }

    public function test_it_can_generate_payu_form_payload()
    {
        $response = MultiPayment::driver('payu')->charge([
            'amount' => 500,
            'txnid' => 'test_txn_123',
            'productinfo' => 'Test Product',
            'firstname' => 'John',
            'email' => 'john@test.com',
        ]);

        $this->assertTrue($response['success']);
        $this->assertEquals('payu', $response['gateway']);
        $this->assertEquals('test_txn_123', $response['transaction_id']);
        $this->assertEquals('form_ready', $response['status']);
        
        $this->assertArrayHasKey('hash', $response['raw']);
        $this->assertEquals('test_key', $response['raw']['key']);
    }
}
