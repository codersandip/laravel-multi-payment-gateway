<?php

namespace VendorName\MultiPayment\Drivers;

use Illuminate\Support\Facades\Http;
use VendorName\MultiPayment\Contracts\PaymentContract;
use VendorName\MultiPayment\Traits\FormatsResponse;
use VendorName\MultiPayment\Exceptions\PaymentGatewayException;
use VendorName\MultiPayment\DTOs\ChargeData;

class PayUDriver implements PaymentContract
{
    use FormatsResponse;

    protected $config;
    protected $baseUrl;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->baseUrl = ($config['test_mode'] ?? true) 
            ? 'https://test.payu.in/_payment'
            : 'https://secure.payu.in/_payment';
    }

    public function charge(array|ChargeData $data): array
    {
        $data = is_array($data) ? ChargeData::fromArray($data) : $data;

        $merchantKey = $this->config['merchant_key'] ?? '';
        $salt = $this->config['salt'] ?? '';
        
        $txnid = $data->receiptId;
        $amount = $data->amount;
        $productinfo = $data->description;
        $firstname = 'Customer';
        $email = $data->email ?? 'test@test.com';

        // Hash pattern: key|txnid|amount|productinfo|firstname|email|||||||||||salt
        $hashString = "{$merchantKey}|{$txnid}|{$amount}|{$productinfo}|{$firstname}|{$email}|||||||||||{$salt}";
        $hash = strtolower(hash('sha512', $hashString));

        $payload = [
            'key' => $merchantKey,
            'txnid' => $txnid,
            'amount' => $amount,
            'productinfo' => $productinfo,
            'firstname' => $firstname,
            'email' => $email,
            'hash' => $hash,
            'surl' => url('/checkout?status=success'),
            'furl' => url('/checkout?status=failed'),
            'url' => $this->baseUrl,
        ];

        return $this->formatResponse(true, 'payu', $txnid, 'form_ready', 'PayU form payload generated', $payload);
    }

    public function refund(string $transactionId, float $amount): array
    {
        $merchantKey = $this->config['merchant_key'] ?? '';
        $salt = $this->config['salt'] ?? '';
        
        $url = ($this->config['test_mode'] ?? true) 
            ? 'https://test.payu.in/merchant/postservice'
            : 'https://info.payu.in/merchant/postservice';

        $command = 'cancel_refund_transaction';
        $hash = strtolower(hash('sha512', "{$merchantKey}|{$command}|{$transactionId}|{$salt}"));

        $response = Http::asForm()->post($url, [
            'key' => $merchantKey,
            'command' => $command,
            'var1' => $transactionId,
            'var2' => $amount,
            'var3' => 'Refund',
            'hash' => $hash
        ]);

        if ($response->failed()) {
            throw new PaymentGatewayException('PayU Refund Failed', 'payu');
        }

        $result = $response->json();
        
        return $this->formatResponse(true, 'payu', $transactionId, 'refunded', 'Refund command executed', $result);
    }

    public function verify(array $payload): array
    {
        $status = $payload['status'] ?? '';
        $txnid = $payload['txnid'] ?? '';
        $hash = $payload['hash'] ?? '';
        $salt = $this->config['salt'] ?? '';
        $amount = $payload['amount'] ?? '';
        $email = $payload['email'] ?? '';
        $firstname = $payload['firstname'] ?? '';
        $productinfo = $payload['productinfo'] ?? '';

        $hashString = "{$salt}|{$status}|||||||||||{$email}|{$firstname}|{$productinfo}|{$amount}|{$txnid}|{$this->config['merchant_key']}";
        $generatedHash = strtolower(hash('sha512', $hashString));

        if ($generatedHash === $hash && $status === 'success') {
            return $this->formatResponse(true, 'payu', $txnid, 'captured', 'Verification successful', $payload);
        }

        throw new PaymentGatewayException('PayU Verification failed', 'payu');
    }

    public function handleWebhook(array $payload, string $signature): array
    {
        return $this->verify($payload);
    }
}
