<?php

namespace App\Models\Payment;

class PaymentMethodStripe extends PaymentMethod
{
    protected $table = "payment_methods";

    function __construct() {
        $this->type = 1;
    }
}
