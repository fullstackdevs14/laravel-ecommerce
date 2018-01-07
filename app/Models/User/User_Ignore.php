<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class User_Ignore extends Model
{
    protected $table = "user_ignore";
    protected $fillable = [
    	'user_id', 'banned_id', 'ip_address', 'last_action',
    ];
}
