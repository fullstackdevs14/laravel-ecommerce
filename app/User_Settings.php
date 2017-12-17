<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User_Settings extends Model
{
    //
    protected $fillable = [
        'user_id', 'newsletter', 'announcement', 'private_msg', 'comment_wallpaper', 'post_response', 'comment_profile', 'favorite'
    ];

    function __construct() {
        $this->newsletter = 1;
        $this->announcement = 1;
        $this->private_msg = 1;
        $this->comment_wallpaper = 1;
        $this->post_response = 1;
        $this->comment_profile = 1;
        $this->favorite = 1;
    }
}
