<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Image_Download extends Model
{
    protected $table = "image_download";
    protected $fillable = [
    	"ip_address", "image_id", "created_at"
    ];
}
