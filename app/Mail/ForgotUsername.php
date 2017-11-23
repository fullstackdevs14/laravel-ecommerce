<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ForgotUsername extends \App\Mail\Clickagy\Mailable
{
    use Queueable, SerializesModels;

    protected $_blastIds = [
        'dn' => '10mvrzq9l42m7'
    ];
    
}