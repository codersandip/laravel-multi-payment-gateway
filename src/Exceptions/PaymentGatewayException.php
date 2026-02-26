<?php

namespace VendorName\MultiPayment\Exceptions;

use Exception;

class PaymentGatewayException extends Exception
{
    protected $gateway;

    /**
     * PaymentGatewayException constructor.
     *
     * @param string $message
     * @param string|null $gateway
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = "", $gateway = null, $code = 0, Exception $previous = null)
    {
        $this->gateway = $gateway;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the gateway that threw the exception.
     *
     * @return string|null
     */
    public function getGateway()
    {
        return $this->gateway;
    }
}
