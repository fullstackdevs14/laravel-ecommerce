<?php

namespace App\Models\Payment;

use \Stripe\Stripe;
use \Stripe\Customer;
use \Stripe\Charge;

class PaymentMethodStripe extends PaymentMethod
{
    protected $table = "payment_methods";

    function __construct() {
        $this->type = 1;
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    function addCard($info, $username, $email) {
        $customer = Customer::create(array(
            "description" => $username,
            "email" => $email,
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

    function charge($amount, $package) {
        $details = json_decode($this->details);

        $charge = Charge::create(array(
            'customer' => $details->customer_id,
            'amount' => $amount,
            'currency' => 'usd',
            'description' => $package
        ));
    }

    function getCharges() {
        $details = json_decode($this->details);

        $charges = Charge::all(array(
            "customer" => $details->customer_id
        ));

        $list = array();
        foreach($charges->data as $charge) {
            array_push($list, array(
                'amount' => $charge->amount,
                'diamonds' => $charge->description,
                'created' => $charge->created
            ));
        }

        return $list;
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
