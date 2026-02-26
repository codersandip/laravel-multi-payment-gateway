<?php

namespace VendorName\MultiPayment\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'raw_response' => 'array',
        'amount' => 'decimal:2',
    ];
}
