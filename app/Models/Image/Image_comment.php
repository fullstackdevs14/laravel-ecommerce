<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Image_comment extends Model
{
    protected $table = "image_comments";
    protected $fillable = [
        'image_id', 'author_id', 'parent_id', 'content', 'sticker', 'author_img', 'parent_img', 'author_name', 'parent_name', 'ip_address', 'last_action',
    ];
}
