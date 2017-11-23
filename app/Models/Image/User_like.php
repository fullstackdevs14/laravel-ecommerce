<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class User_like extends Model
{
    protected $table = "user_likes";
    protected $fillable = [
    	'user_id', 'image_id',
    ];
}
