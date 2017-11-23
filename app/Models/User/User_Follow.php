<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class User_Follow extends Model
{
    protected $table = "user_follows";
    protected $fillable = [
    	'user_id', 'follower_id'
    ];
}
