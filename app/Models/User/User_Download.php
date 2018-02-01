<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class User_Download extends Model
{
    protected $table = "user_download";
    protected $fillable = [
    	"ip_address","user_id", "image_id", "created_at"
    ];
}
