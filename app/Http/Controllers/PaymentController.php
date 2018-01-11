<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use App\Models\Payment\PaymentMethod;
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
            $method->charge($values[$count]);

            // update diamond
            $diamond = User::where('id', $user->id)->first();
            $diamond->diamond += $count;
            $diamond->save();

            return $this->success($diamond);
        }

        return $this->fail('Unsupported Method');
    }

    private function success($user) {
        return Response()->json([
            'success' => 1,
            'user' => $user
        ]);
    }

    private function fail($msg) {
        return Response()->json([
            'success' => 0,
            'message' => $msg
        ]);
    }

    // public function allCustomers(Request $request) {
    //     return Response()->json([
    //         'list' => PaymentMethodStripe::allCustomers()
    //     ]);
    // }

    // public function deleteCustomer(Request $request) {
    //     PaymentMethodStripe::deleteCustomer(
    //         $request->input('customer_id')
    //     );

    //     return Response()->json([
    //         'success' => 1
    //     ]);
    // }
}
