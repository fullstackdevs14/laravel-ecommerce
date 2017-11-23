<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\ValidateEmail;
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\Exception\ClientException;

class RegisteredEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        
    }

    /**
     * Handle the event.
     *
     * @param  Registered  $event
     * @return void
     */
    public function handle(Registered $event)
    {

        $user = $event->user;

        $email = $user->email;

        $validateEmail = new ValidateEmail();
        $token = $user->verification_token;
        $url = route('public.account.verify-email', ['token' => $token]);
        
        $validateEmail->to($email, $user->username)
                      ->setViewData([
            'VALIDATEURL' => $url
        ]);
                                  
        Mail::queue($validateEmail);
    }
}
