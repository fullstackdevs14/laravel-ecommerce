<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Gallery_Follow extends Model
{
    protected $table = "gallery_follows";
    protected $fillable = [
        'gallery_id', 'follower_id',
    ];
}
