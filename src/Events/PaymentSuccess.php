<?php

namespace VendorName\MultiPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccess
{
    use Dispatchable, SerializesModels;

    public $response;

    /**
     * Create a new event instance.
     *
     * @param array $response
     */
    public function __construct(array $response)
    {
        $this->response = $response;
    }
}
