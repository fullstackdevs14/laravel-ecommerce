<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use LaravelCloudSearch\Eloquent\Searchable;

use App\Models\Image\Image_comment;
use App\Models\Image\Image_tag;

class ImageModel extends Model
{
	use Searchable;

	protected $table = "images";
   	protected $fillable = [
        's3_id', 's3_cached', 'title', 'description', 'downloads', 'favorites', 'upload_by', 'upload_date', 'orig_width', 'orig_height', 'file_size', 'likes', 'shares'
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
		    'title' => $this->title,
		    'description' => $this->description,
            'comments' => $this->getCommentTexts(),
            'tags' => $this->getWallpaperTags(),
		];
    }

}
