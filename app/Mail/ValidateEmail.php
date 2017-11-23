<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class ValidateEmail extends \App\Mail\Clickagy\Mailable
{
    use Queueable, SerializesModels;

    protected $_blastIds = [
        'dn' => 'al6skp9mreyl'
    ];
    
}
