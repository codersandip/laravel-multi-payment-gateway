<?php

namespace VendorName\MultiPayment\DTOs;

use VendorName\MultiPayment\Enums\Currency;
use InvalidArgumentException;

class ChargeData
{
    public function __construct(
        public readonly float $amount,
        public readonly Currency $currency = Currency::INR,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $receiptId = null,
        public readonly ?string $description = null,
        public readonly array $metadata = []
    ) {
        if ($this->amount <= 0) {
            throw new InvalidArgumentException("Payment amount must be greater than zero.");
        }
    }

    /**
     * Build from native array format
     */
    public static function fromArray(array $data): self
    {
        $currency = isset($data['currency']) 
            ? (is_string($data['currency']) ? Currency::from(strtoupper($data['currency'])) : $data['currency']) 
            : Currency::INR;

        return new self(
            amount: (float) $data['amount'],
            currency: $currency,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            receiptId: $data['receipt_id'] ?? $data['receipt'] ?? $data['txnid'] ?? uniqid('txn_'),
            description: $data['description'] ?? $data['productinfo'] ?? 'Order Payment',
            metadata: $data['metadata'] ?? []
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency->value,
            'email' => $this->email,
            'phone' => $this->phone,
            'receipt' => $this->receiptId,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ];
    }
}
