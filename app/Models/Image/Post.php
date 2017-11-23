<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = "posts";
    protected $fillable = [
        'type', 'image_id', 'author_id', 'user_id', 'content'
    ];
}
