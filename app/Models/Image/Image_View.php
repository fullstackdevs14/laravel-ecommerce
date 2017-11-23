<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Image_View extends Model
{
    protected $table = "image_view";
    protected $fillable = [
    	"ip_address", "image_id", "created_at"
    ];
}
