<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    //
    protected $fillable = ['user_id', 'type', 'details'];
}
