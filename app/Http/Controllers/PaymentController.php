<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use JWTAuth;
use App\Models\Payment\PaymentMethod;

class PaymentController extends Controller
{
    public function getMethods() {
        $user = JWTAuth::parseToken()->authenticate();

        $methods = PaymentMethod::where('user_id', $user->id)
                    ->select('type', 'details')
                    ->get();

		foreach ($methods as $method)
		{
            if ($method->type == 1) { // If method is stripe
                $details = json_decode($method->details);
                
                $method->details = [
                    'card' => substr($details->card, -4),
                    'len' => strlen($details->card)
                ];
            }
		}

        return Response()->json([
            'methods' => $methods
        ]);
    }

    public function addMethod(Request $request) {
        $user = JWTAuth::parseToken()->authenticate();
        $type = $request->input('type');

        if (!$type) {
            return Response()->json([
                'success' => 0,
                'message' => 'Insufficient Information'
            ]);
        }

        $old = PaymentMethod::where('user_id', $user->id)
            ->where('type', $type);

        if ($old->exists()) {
            return Response()->json([
                'success' => 0,
                'message' => 'Already exists'
            ]);
        }

        $method = new PaymentMethod();
        $method->user_id = $user->id;
        $method->type = $type;
        $method->details = json_encode([
            'card' => "424242424242424"
        ]);
        $method->save();

        return Response()->json([
            'success' => 1
        ]);
    }
}
