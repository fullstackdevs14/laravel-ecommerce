<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Event;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\WelcomeSignup;
use Illuminate\Support\Facades\Mail;
use GuzzleHttp\Exception\ClientException;

class EventListener
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
    public function handle(Event $event)
    {

        $user = $event->user;

        $email = $user->email;

        $validateEmail = new WelcomeSignup();
        $token = $user->token;
        $content = "Congratulation!";
        
        $validateEmail->to($email, $user->username)
                      ->setViewData([
            'FNAME' => $user->username,
            'CONTENT' => $content
        ]);
                                  
        Mail::queue($validateEmail);
    }
}
