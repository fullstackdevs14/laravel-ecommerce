<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Users extends Model
{
	use \HighIdeas\UsersOnline\Traits\UsersOnlineTrait;
	
    protected $table = "users";
    protected $fillable = [
        'avatar', 'cover_img', 'id',
    ];
}
