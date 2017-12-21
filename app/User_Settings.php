<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class User_Settings extends Model
{
    protected $table = "user_settings";
    protected $fillable = [
        'user_id', 'email', 'browser', 'private_msg', 'comment_wallpaper', 'comment_profile', 'follow_profile', 'favorite_wallpaper', 'wallpaper_gratuity', 'achievement', 'follower_levelup', 'newsletter'
    ];

    function __construct() {
        $this->email = 1;
        $this->browser = 1;
        $this->private_msg = 1;
        $this->comment_wallpaper = 1;
        $this->comment_profile = 1;
        $this->follow_profile = 1;
        $this->favorite_wallpaper = 0;
        $this->wallpaper_gratuity = 1;
        $this->achievement = 1;
        $this->follower_levelup = 1;
        $this->newsletter = 1;
    }
}