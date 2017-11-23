<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Image_thumbnail extends Model
{
    protected $table = "image_thumbnails";
    protected $fillable = [
        's3_id', 'image_id', 'width', 'height',
    ];
}
