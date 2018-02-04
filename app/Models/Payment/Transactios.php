<?php

namespace App\Models\Payment;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    //
    protected $fillable = ['type', 'sender', 'receiver', 'value', 'status', 'ip_address'];
}
