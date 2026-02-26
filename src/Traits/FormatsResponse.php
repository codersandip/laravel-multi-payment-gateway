<?php

namespace VendorName\MultiPayment\Traits;

trait FormatsResponse
{
    /**
     * Format a standard payment response array.
     *
     * @param bool $success
     * @param string $gateway
     * @param string|null $transactionId
     * @param string $status
     * @param string|null $message
     * @param array $raw
     * @return array
     */
    protected function formatResponse(
        bool $success,
        string $gateway,
        ?string $transactionId,
        string $status,
        ?string $message,
        array $raw = []
    ): array {
        return [
            'success'        => $success,
            'gateway'        => $gateway,
            'transaction_id' => $transactionId,
            'status'         => $status,
            'message'        => $message,
            'raw'            => $raw,
        ];
    }
}
