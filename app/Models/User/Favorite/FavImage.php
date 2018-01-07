<?php

namespace App\Models\User\Favorite;

use Illuminate\Database\Eloquent\Model;

class FavImage extends Model
{
    protected $table = 'user_fav_images';
    protected $fillable = [
    	'folder_id', 'image_id', 'user_id', 'ip_address', 'last_action', 
    ];
}
