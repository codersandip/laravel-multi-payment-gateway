<?php

namespace VendorName\MultiPayment\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case CAPTURED = 'captured';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';
    case FORM_READY = 'form_ready';
}
