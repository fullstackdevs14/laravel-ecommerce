<?php

namespace App\Models\Image;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $table = "reports";
    protected $fillable = [
        'image_id', 'category', 'content', 'type', 'ip_address' , 'last_action',
    ];
}
