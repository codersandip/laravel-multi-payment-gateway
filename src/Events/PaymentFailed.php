<?php

namespace VendorName\MultiPayment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Exception;

class PaymentFailed
{
    use Dispatchable, SerializesModels;

    public $exception;
    public $driverName;

    /**
     * Create a new event instance.
     *
     * @param Exception $exception
     * @param string $driverName
     */
    public function __construct(Exception $exception, string $driverName)
    {
        $this->exception = $exception;
        $this->driverName = $driverName;
    }
}
