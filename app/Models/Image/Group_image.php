<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Group_image extends Model
{
    protected $table = "group_image";
    protected $fillable = [
        'group_id', 'image_id',
    ];
}
