<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Gallery_group extends Model
{
    protected $table = "gallery_groups";
    protected $fillable = ['gallery_id', 'group_id'];
}
