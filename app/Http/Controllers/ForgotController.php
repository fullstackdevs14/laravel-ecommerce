<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use App\Mail\ForgotPassword;
use App\Mail\ForgotUsername;
use App\Models\User\User_Email;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\Exception\ClientException;
use App\Mail\ForgotEmail;

class ForgotController extends Controller
{

    public function sendForgotPasswordMail(Request $request)
    {	
        // get parameter from API request
        $email = $request->input('email');
        // get user email instance from variable
        $user = User_Email::where('email', $email)->first();
        if($user) {
            $user = User::where('id', $user->user_id)->first();
            $validateEmail = new ForgotPassword();
            $url = env('FORGOT_PASSWORD');
            $url .= $user->token;
            // send email by validate
            $validateEmail->to($email, $user->username)
                          ->setViewData([
                'FNAME' => $user->first_name,
                'CONTENT' => $url
            ]);         
    
            Mail::queue($validateEmail);
            return Response()->json([
                "result" => "Successfully sent a password reset link to your email!",
                "code" => 1
            ]);
        }
        else
            return Response()->json([
                "result" => "Could not locate an account with that email address. Please double-check it and try again.",
                "code" => 0
            ]);
    }	

    public function sendForgotUsernameMail(Request $request)
    {
        // get parameter from API request
        $email = $request->input('email');
        // get user email instance from variable
        $user_email = User_Email::where('email', $email)->first();
        if($user_email) {
            $user = User::getUserFromEmail($user_email);
            $validateEmail = new ForgotUsername();
            $content = 'Your Username is  '.$user->username;
            // send email by validate
            $validateEmail->to($email, $user->username)
                          ->setViewData([
                'FNAME' => $user->first_name,
                'CONTENT' => $content
            ]);
                          
            Mail::queue($validateEmail);
            return Response()->json([
                "result" => "Successfully sent a username reminder to your email!",
                "code" => 1
            ]);
        }
        else
            return Response()->json([
                "result" => "Could not locate an account with that email address. Please double-check it and try again.",
                "code" => 0
            ]);
    }

    public function changePassword(Request $request)
    {
    	$token = $request->input('token');

    	$user = User::whereToken($token)->first();
    	if($user) {
	    	$user->password = bcrypt($request->input('password'));
	    	$user->save();
	    	return Response()->json([
	    		'result' => 'Successful'
			]);
		}

		return Response()->json([
			'result' => 'Your Credentials don`t Exist.!'
		]);
    }
}
