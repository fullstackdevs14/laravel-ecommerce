<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ImageModel extends Model
{
	protected $table = "images";
   	protected $fillable = [
        's3_id', 's3_cached', 'title', 'description', 'downloads', 'favorites', 'upload_by', 'upload_date', 'orig_width', 'orig_height', 'file_size', 'likes', 'shares'
    ];
}
