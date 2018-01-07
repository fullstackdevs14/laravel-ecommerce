<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class User_Report extends Model
{
    protected $table = "user_reports";
    protected $fillable = [
    	'user_id', 'content', 'is_solved', 'ip_address', 'last_action',
    ];
}
