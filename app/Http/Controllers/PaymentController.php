<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use \Stripe\Charge;
use \Stripe\Stripe;
use App\Models\Payment\PaymentMethod;
use App\Models\Payment\Transaction;
use App\Models\Payment\PaymentMethodStripe;
use App\Models\User\User_Email;
use App\User;

class PaymentController extends Controller
{
    public function getMethods() {
        $user = JWTAuth::parseToken()->authenticate();

        $methods = PaymentMethod::where('user_id', $user->id)
                    ->select('type', 'details')
                    ->get();

        $result = array();
        foreach ($methods as $method)
        {
            if ($method->type == 1) { // If method is stripe
                $details = json_decode($method->details);
                $result['stripe'] = $details->card;
            }
        }

        return Response()->json($result);
    }

    public function addMethod(Request $request) {
        $user = JWTAuth::parseToken()->authenticate();
        $type = $request->input('type');
        $data = $request->input('data');

        if (!$type && !$data) {
            return $this->fail('Insufficient Information');
        }

        // Delete existing method
        $old = PaymentMethod::where('user_id', $user->id)
            ->where('type', $type);
        if ($old->exists()) {
            if ($type == 1) {
                $oldCard = PaymentMethodStripe::where('user_id', $user->id)
                    ->where('type', $type)
                    ->first();
                $oldCard->deleteCard();
            }
        }

        $user_email = User_Email::where('user_id', $user->id)
            ->where('primary', 1)
            ->select('email')
            ->first();

        if (!$user_email) {
            return $this->fail('Invalid user');
        }

        // Add new method
        if ($type == 1) {
            $method = new PaymentMethodStripe();
            $method->user_id = $user->id;
            $method->addCard($data, $user->username, $user_email->email);

            return $this->success();
        }

        return $this->fail('Unsupported Method');
    }

    public function deleteMethod(Request $request) {
        $user = JWTAuth::parseToken()->authenticate();
        $type = $request->input('type');

        if ($type == 1) {
            $method = PaymentMethodStripe::where('user_id', $user->id)
                ->where('type', $type)
                ->first();
            $method->deleteCard();

            return $this->success();
        }

        return $this->fail('Unsupported Method');
    }

    public function buyDiamonds(Request $request) {
        $user = JWTAuth::parseToken()->authenticate();
        $type = $request->input('type');
        $count = $request->input('count');

        $values = [
            "125" => 999,
            "325" => 1999,
            "1000" => 4999,
            "2500" => 9999
        ];

        if(!array_key_exists($count, $values)) {
            return $this->fail('Invalid diamond counts');
        }

        if ($type == 1) {
            $method = PaymentMethodStripe::where('user_id', $user->id)
                ->where('type', $type)
                ->first();
            $method->charge($values[$count], $count);

            // update diamond
            $diamond = User::where('id', $user->id)->first();
            $diamond->diamond += $count;
            $diamond->save();

            return Response()->json([
                'success' => 1,
                'user' => $diamond
            ]);
        }

        return $this->fail('Unsupported Method');
    }

    public function getTransactions() {
        $user = JWTAuth::parseToken()->authenticate();
        $method = PaymentMethodStripe::where('user_id', $user->id)
            ->where('type', 1)->first();
        $charges = $method->getCharges();

        return Response()->json([
            'list' => $charges
        ]);
    }

    public function sendDiamond(Request $request)
    {
        $receiver_id = $request->input('receiver');
        $diamond = $request->input('amount');
        $is_msg = $request->input('is_msg');
        $ip = $request->input('ip_address');

        $sender = $request->user;
        if ($sender->diamond < $diamond) {
            return Response()->json([
                'error' => 'not_enough_diamonds'
            ], 400); 
        }

        $receiver = User::find($receiver_id);

            // if receiver not exist in database
        if(!$receiver)
        {
            return Response()->json([
                'result' => 0
            ], 201);
        }

        $sender->diamond -= $diamond;
        $sender->save();

        $transaction = new Transaction();
        $transaction->type = 1;
        $transaction->sender = $sender->id;
        $transaction->receiver = $receiver->id;
        $transaction->value = $diamond;
        $transaction->ip_address = $ip;
        
        if ($is_msg) {
            $transaction->status = 0;
        } else {
            $transaction->status = 1;
            $receiver->diamond += $diamond;
            $receiver->save();
        }

        $transaction->save();
        
        return Response()->json([
            'transaction' => $transaction->id,
            'success' => 1
        ]);
    }

    public function collectDiamond(Request $request) {
        $tid = $request->input('tid');

        $transaction = Transaction::find($tid);
        if ($transaction) {
            if ($transaction->status == 1) {
                return $this->fail('You have already collected the diamonds.');
            }

            if ($transaction->receiver != $request->user->id) {
                return $this->fail('Invalid transaction');
            }

            $receiver = User::find($transaction->receiver);
            $receiver->diamond += $transaction->value;
            $receiver->save();

            $transaction->status = 1;
            $transaction->save();

            return $this->success();
        }

        return $this->fail('Transaction not found');
    }

    private function success() {
        return Response()->json([
            'success' => 1
        ]);
    }

    private function fail($msg) {
        return Response()->json([
            'success' => 0,
            'message' => $msg
        ]);
    }
}
