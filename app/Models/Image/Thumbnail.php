<?php
namespace App\Models\Image;
use App\Models\Image;
use stojg\crop\CropEntropy;
use Illuminate\Support\Facades\Storage;
class Thumbnail extends \Eloquent
{
    protected $table = "image_thumbnails";
    
    static public function boot()
    {
        static::deleting(function(self $model) {
            Storage::disk('thumbnails')->delete($model->s3_id);
            return true;
        });  
    }
    
    public function originalImage()
    {
        return $this->belongsTo('App\Models\Image', 'image_id');
    }
    
    public function scopeByDimensions($query, $width, $height)
    {
        return $query->where('width', '=', $width)
                     ->where('height', '=', $height);
    }
    
    public function isPublic()
    {
        return ($this->width * $this->height) <= 360000;
    }
    
    public function getS3Name()
    {
        return "{$this->width}x{$this->height}_{$this->originalImage->s3_id}";
    }
    
    public function getImagick()
    {
        $data = Storage::disk('thumbnails')->get($this->getS3Name($this->width, $this->height));
        
        $retval = new \Imagick();
        $retval->readImageBlob($data);
        
        return $retval;
    }
    
    public function getUrl()
    {
        if($this->isPublic()) {
            return Storage::disk('thumbnails')->url($this->getS3Name($this->width, $this->height));
        }
        
        return route('public.image', [
            'id' => $this->originalImage->s3_id,
            'dia' => "{$this->width}x{$this->height}"
        ]);
    }
    
    static public function crop($filestream, $width, $height)
    {
        $retval = new \Imagick();
        $retval->readImageBlob($filestream);
        // create Entropy cropping object  
        $entropy_image = $retval;
        $cropper = new CropEntropy($entropy_image);
        $cache_photo = $cropper->resizeAndCrop($width, $height);
        $cache_photo->setImageFormat('png');

        if(!$cache_photo) {
            throw new \Exception("Failed to resize image");
        }
        $resource = $cache_photo->getImageBlob();
        return $resource;
    }

    static public function generate(Image $imageModel, $width, $height)
    {
        $thumbnailModel = new static();
        $thumbnailModel->width = $width;
        $thumbnailModel->height = $height;
        $thumbnailModel->image_id = $imageModel->id;
        
        $thumbnailModel->load('originalImage');
        
        $image = $imageModel->getImagick();
        
        $cropper = new CropEntropy($image);
        
        $thumbnail = $cropper->resizeAndCrop($width, $height);
        $thumbnail->setImageFormat('png');
        
        if(!$thumbnail) {
            throw new \Exception("Failed to resize image");
        }
        
        Storage::disk('thumbnails')
               ->put($thumbnailModel->getS3Name(), $thumbnail->getImageBlob());
        
        if($thumbnailModel->isPublic()) {
            Storage::disk('thumbnails')
                   ->setVisibility($thumbnailModel->getS3Name(), 'public');
        }
        
        $thumbnailModel->s3_id = $thumbnailModel->getS3Name();
        
        return $thumbnailModel;
    }
}