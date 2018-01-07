<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\User_Email;
use App\Models\Image\Post;
use App\Models\Image\Image_tag;
use App\Models\User\User_Follow;
use App\ImageModel;

use LaravelCloudSearch\Eloquent\Searchable;

class User extends Authenticatable
{
    use Notifiable;
    use Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'lastname', 'username', 'email', 'password', 'avatar', 'cover_img', 'about', 'join_ip', 'last_login_ip', 'joined', 'last_login',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function($user) {
            $user->token = str_random(40);
        });
    }

    public static function getSearchItem($target_id, $user_id)
    {
        $item = User::where('id', $target_id)
                ->first();
            // get view
        $follower = User_Follow::where('user_follows.follower_id','=',$item->id)
        ->leftJoin('users', 'users.id', '=', 'user_follows.user_id')
        ->orderBy('user_follows.created_at', 'desc')
        ->limit(4)
        ->get();
        $item->follow_count = User_Follow::where('follower_id','=',$item->id)->count();
        $item->follow = $follower;
            // get favorite
        $image_upload = ImageModel::where('upload_by', '=', $item->id)
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();
        $image_last_avatar = ImageModel::where('upload_by', '=', $item->id)
        ->orderBy('created_at', 'desc')
        ->offset(4)
        ->limit(1)
        ->first();
        if($image_last_avatar)
            $item->upload_last_avatar = $image_last_avatar->s3_id;
        $item->upload_count = ImageModel::where('upload_by', '=', $item->id)->count();
        $item->upload = $image_upload;
        if ($user_id == $item->id) 
            $is_follow = 1;
        else{
            $is_follow = User_Follow::where('follower_id', $item->id)
            ->where('user_id', $user_id)
            ->get()
            ->count();
        }
        $item->is_follow = $is_follow;

        return $item;
    }

    public static function getUserFromEmail(User_Email $obj)
    {
        $id = $obj->user_id;
        $user = User::where('id', $id)->first();
        return $user;
    }


    public function getCommentTexts()
    {
        $commentTexts = [];

        $comments = Post::where('user_id', '=', $this->id)->get();
        foreach ($comments as $comment) {
            $commentTexts[] = $comment->content;
        }

        return $commentTexts;
    }

    public function getSearchDocument()
    {
        return [
            'target_id' => $this->id,
            'title' => $this->username,
            'description' => $this->about,
            'comments' => $this->getCommentTexts(),
            'tags' => [],
        ];
    }
}
