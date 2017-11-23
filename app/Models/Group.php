<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $table = "groups";
    protected $fillable = [
        'name', 'slug', 'status', 'avatar', 'cover_img', 'description', 'owner_id', 'primary_image_id', 'image_count', 'gallery_id',
    ];
}
