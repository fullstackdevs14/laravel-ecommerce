<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\User_Email;
use App\Models\Image\Post;
use App\Models\Image\Image_tag;

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
        'firstname', 'lastname', 'username', 'email', 'password', 'avatar', 'cover_img', 'about',
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
