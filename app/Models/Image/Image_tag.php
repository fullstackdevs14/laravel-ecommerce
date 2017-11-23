<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Image_tag extends Model
{
    protected $table = "image_tags";
    protected $fillable = [
        'tag_id', 'image_id',
    ];
}
