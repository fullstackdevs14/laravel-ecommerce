<?php

namespace App\Models\Payment;

use \Stripe\Stripe;
use \Stripe\Customer;

class PaymentMethodStripe extends PaymentMethod
{
    protected $table = "payment_methods";

    function __construct() {
        $this->type = 1;
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    function addCard($info, $username) {
        $customer = Customer::create(array(
            "description" => $username,
            "source" => $info['stripeToken']
        ));

        $this->details = json_encode([
            'customer_id' => $customer->id,
            'card' => $info['card']
        ]);
        $this->save();
    }

    function deleteCard() {
        $details = json_decode($this->details);

        $customer = Customer::retrieve($details->customer_id);
        $customer->delete();
        parent::delete();
    }

    // static function allCustomers() {
    //     Stripe::setApiKey(env('STRIPE_SECRET'));
    //     return Customer::all();
    // }

    // static function deleteCustomer($customer_id) {
    //     Stripe::setApiKey(env('STRIPE_SECRET'));
    //     $customer = Customer::retrieve($customer_id);
    //     $customer->delete();
    // }
}
