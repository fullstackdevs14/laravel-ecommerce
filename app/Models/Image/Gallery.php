<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    protected $table = "galleries";
    protected $fillable = ['name', 'description', 'avatar'];
}
