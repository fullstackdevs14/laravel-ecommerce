<?php

namespace App\Models\User\Favorite;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $table = "user_fav_folders";
    protected $fillable = ["id", "user_id", "text", "parent"];
}
