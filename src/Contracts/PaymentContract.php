<?php

namespace VendorName\MultiPayment\Contracts;

interface PaymentContract
{
    /**
     * Charge a payment
     *
     * @param array|\VendorName\MultiPayment\DTOs\ChargeData $data
     * @return array
     */
    public function charge(array|\VendorName\MultiPayment\DTOs\ChargeData $data): array;

    /**
     * Refund a transaction
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function refund(string $transactionId, float $amount): array;

    /**
     * Verify a payment payload (e.g., from a redirect or polling)
     *
     * @param array $payload
     * @return array
     */
    public function verify(array $payload): array;

    /**
     * Handle incoming webhooks
     *
     * @param array $payload
     * @param string $signature
     * @return array
     */
    public function handleWebhook(array $payload, string $signature): array;
}
