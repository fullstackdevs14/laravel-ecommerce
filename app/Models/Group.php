<?php

namespace App\Models;

use App\Models\Image\Group_Follows;
use App\Models\Image\Group_image;

use LaravelCloudSearch\Eloquent\Searchable;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
	use Searchable;

    protected $table = "groups";
    protected $fillable = [
        'name', 'slug', 'status', 'avatar', 'cover_img', 'description', 'owner_id', 'primary_image_id', 'image_count', 'gallery_id', 'ip_address', 'last_action',
    ];

    public function getCommentTexts()
    {
        $commentTexts = [];

        $comments = Image_comment::where('image_id', '=', $this->id)->get();
        foreach ($comments as $comment) {
            $commentTexts[] = $comment->content;
        }

        return $commentTexts;
    }

    public function getWallpaperTags()
    {
        $tagTexts = [];

        $tags = Image_tag::leftJoin('tags', 'tags.id', '=', 'image_tags.tag_id')
                ->where('image_tags.image_id', '=', $this->id)
                ->get();
        foreach ($tags as $tag) {
            $tagTexts[] = $tag->name;
        }

        return $tagTexts;
    }

    public static function getSearchItem($target_index, $user_id)
    {
        $item = Group::where('id', $target_index)
        ->first();
        // get view
        $follower = Group_Follows::where('group_follows.group_id','=',$item->id)
        ->leftJoin('users', 'users.id', '=', 'group_follows.follower_id')
        ->orderBy('users.created_at', 'desc')
        ->limit(4)
        ->get();
        $item->follow_count = Group_Follows::where('group_id','=',$item->id)->count();
        $item->follow = $follower;
            // get group image
        $group_image = Group_image::where('group_id', $item->name)
        ->leftJoin('images', 'images.id', '=', 'group_image.image_id')
        ->orderBy('group_image.created_at', 'desc')
        ->limit(3)
        ->get();
        $image_last_avatar = Group_image::where('group_id', $item->name)
        ->leftJoin('images', 'images.id', '=', 'group_image.image_id')
        ->orderBy('group_image.created_at', 'desc')
        ->offset(4)
        ->limit(1)
        ->first();
        if($image_last_avatar)
            $item->upload_last_avatar = $image_last_avatar->s3_id;
        $item->upload_count = Group_image::where('group_id', '=', $item->name)->count();
        $item->upload = $group_image;
            // if followed by request holder
        $is_follow = Group_Follows::where('group_id', $item->id)
        ->where('follower_id', $user_id)
        ->get()
        ->count();
        $item->is_follow = $is_follow;

        return $item;
    }

    public function getSearchDocument()
    {
		return [
            'target_id' => $this->id,
		    'title' => $this->name,
		    'description' => $this->description,
            'comments' => [],
            'tags' => [],
		];
    }
}
