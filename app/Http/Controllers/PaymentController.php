<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use App\Models\Payment\PaymentMethod;
use App\Models\Payment\PaymentMethodStripe;

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
            return Response()->json([
                'success' => 0,
                'message' => 'Insufficient Information'
            ]);
        }

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

        if ($type == 1) {
            $method = new PaymentMethodStripe();
            $method->user_id = $user->id;
            $method->addCard($data, $user->username);

            return Response()->json([
                'success' => 1
            ]);
        }

        return Response()->json([
            'success' => 0,
            'message' => 'Unsupported method'
        ]);
    }

    public function deleteMethod(Request $request) {
        $user = JWTAuth::parseToken()->authenticate();
        $type = $request->input('type');

        if ($type == 1) {
            $method = PaymentMethodStripe::where('user_id', $user->id)
                ->where('type', $type)
                ->first();
            $method->deleteCard();

            return Response()->json([
                'success' => 1
            ]);
        }

        return Response()->json([
            'success' => 0,
            'type' => $type,
            'message' => 'Unsupported Method'
        ]);
    }

    public function allCustomers(Request $request) {
        return Response()->json([
            'list' => PaymentMethodStripe::allCustomers()
        ]);
    }

    public function deleteCustomer(Request $request) {
        PaymentMethodStripe::deleteCustomer(
            $request->input('customer_id')
        );

        return Response()->json([
            'success' => 1
        ]);
    }
}
