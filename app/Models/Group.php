<?php

namespace App\Models;

use LaravelCloudSearch\Eloquent\Searchable;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
	use Searchable;

    protected $table = "groups";
    protected $fillable = [
        'name', 'slug', 'status', 'avatar', 'cover_img', 'description', 'owner_id', 'primary_image_id', 'image_count', 'gallery_id',
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
