<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class Notification extends \App\Mail\Clickagy\Mailable 
{
    use Queueable, SerializesModels;

    protected $_blastIds = [
        'dn' => '10mvrzq9l42m7'
    ];
    
}
