<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class User_Social extends Model
{
    //
    protected $table = "user_social";
    protected $fillable = [
    	"ip_address","user_id", "image_id", "created_at"
    ];
}
