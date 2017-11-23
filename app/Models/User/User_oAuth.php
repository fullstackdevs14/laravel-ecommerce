<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class User_oAuth extends Model
{
    protected $table = "user_oauth_tokens";
    protected $fillable = [
    	"user_id", "driver", "token"
    ];
}
