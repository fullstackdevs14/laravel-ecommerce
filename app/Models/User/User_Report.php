<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class User_Report extends Model
{
    protected $table = "user__reports";
    protected $fillable = [
    	'user_id', 'content', 'is_solved',
    ];
}
