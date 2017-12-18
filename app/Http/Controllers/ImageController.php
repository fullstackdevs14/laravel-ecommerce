<?php

namespace App\Http\Controllers;

use App\User;
use App\ImageModel;
use App\Models\Tag;

use stojg\crop\CropEntropy;

use App\Models\Image\Image_tag;
use App\Models\Image\Image_thumbnail;
use App\Models\Image\Image_comment;
use App\Models\Image\Image_View;
use App\Models\Image\User_like;
use App\Models\Image\Image_Download;
use App\Models\Image\Group_image;
use App\Models\Image\Report;
use App\Models\Image\Gallery;
use App\Models\Image\Gallery_Follows;
use App\Models\Image\Group_Follows;
use App\Models\Image\Gallery_group;

use App\Models\User\User_Ignore;
use App\Models\User\User_Follow;
use App\Models\User\User_Social;
use App\Models\User\Users;
use App\Models\User\Favorite\Folder;
use App\Models\User\Favorite\FavImage;

use App\Models\Group;
use App\Models\Log;
use Illuminate\Http\Request;
use Intervention\Image\ImageManagerStatic as Image;
use Intervention\Image\ImageManager;
use Aws\S3\S3Client;
use Aws\S3\PostObjectV4;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

// use App\Models\Image;
use App\Util\StringUtils;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

// use Crypt
use Illuminate\Support\Facades\Crypt;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

use Aws\Rekognition\RekognitionClient;
use App\Mail\Invite;

class ImageController extends Controller
{
    // protected $_s3Client;


    // public function __construct(S3Client $client)
    // {
    //     // $this->_s3Client = $client;
    //     //setTimezone(new DateTimeZone('US/'));
    // }

    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function follow(Request $request)
    {
        $user_id = $request->input('user_id');
        $follower_id = $request->input('follower_id');
        if(User_Follow::where('user_id', $user_id)->where('follower_id', $follower_id)->first())
            return Response()->json([
                "result" => "Already"
                ], 200);
        $user_follow = new User_Follow([
            'user_id' => $request->input('user_id'),
            'follower_id' => $request->input('follower_id')
            ]);

        $user_follow->save();

        return Response()->json([
            "result" => "Success"
            ], 200);
    }

    /**
     * Resize/Crop a resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function resize(Request $request)
    {
        // get parameters from request
        $image_id = $request->input('img_id');
        $width = $request->input('width');
        $height = $request->input('height');

        $image = ImageModel::find($image_id);
        // s3 bucket variables
        $s3 = Storage::disk('s3');
        $cache_s3 = Storage::disk('thumbnails');

        // capture s3 cache image
        $index = strpos($image->s3_id, ".");
        
        // create session expired after 20 min
        $time = intval($image->s3_cached);
        $cache_url = substr_replace($image->s3_id, "-".$width."x".$height."-DesktopNexus", $index, 0);

        // check if time is expired
        if(!$cache_s3->has($cache_url)) 
        {
            // recreate cache file
            $url = $s3->get($image->s3_id);
            
            $retval = new \Imagick();
            $retval->readImageBlob($url);
            
            // create Entropy cropping object  
            $entropy_image = $retval;

            $cropper = new CropEntropy($entropy_image);
            $cache_photo = $cropper->resizeAndCrop($width, $height);
            $cache_photo->setImageFormat('png');

            if(!$cache_photo) {
                throw new \Exception("Failed to resize image");
            }
            // $cache_photo = Image::make($url)->fit($width, $height);
            $resource = $cache_photo->getImageBlob();
            $cache_s3->put($cache_url, $resource, [ 'visibility' => 'public', 'Expires' => '+20 minutes' ]);
        }
        
        // create signed URL
        $client = $cache_s3->getDriver()->getAdapter()->getClient();
        $expiry = "+1 minutes";

        $command = $client->getCommand('GetObject', [
            'Bucket' => \Config::get('filesystems.disks.thumbnails.bucket'),
            'Key'    => $cache_url
            ]);
        $request = $client->createPresignedRequest($command, $expiry);

        // return with image Url
        return response()->json([
            "result" => (string) $request->getUri()
            ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->hasFile('photo') && $request->input('uid')){
            $photo = $request->file('photo');

            $originName = $photo->getClientOriginalName();
            $title = explode('.', $originName)[0];
            $title = ucwords(preg_replace('/[_-]/', ' ', $title));
            $file_size = $photo->getClientSize();

            $filename = time() . "." . $photo->getClientOriginalExtension();

            list($orig_width, $orig_height) = getimagesize($photo);

            // set Image limitation(1024 * 768)
            if ($orig_width<1024 && $orig_height<768)
            {
                return response()->json([
                    'code' => 0
                    ], 200);
            }

            // save Image to the s3 Cache
            $s3 = Storage::disk('s3');
            $s3->put($filename, file_get_contents($photo), 'public');

            // $s3->put('uploads/'. $filename, fopen($photo, 'r+'), 'public');

            $thumbnails = Storage::disk('thumbnails');
            
            $manager = new ImageManager(array('driver' => 'imagick'));
            $thumb_photo = Image::make($photo)->fit(150, 150);
            $resource = $thumb_photo->stream()->detach();
            $thumbnails->put($filename, $resource, 'public');

            $thumb_photo = Image::make($photo)->fit(450, intval(450*$orig_height/$orig_width));
            $resource = $thumb_photo->stream()->detach();

            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $thumbnails->put($thumb_filename, $resource, 'public');

            /**
             *  Image Rekognition
             */
            try {
                $rekognition = new RekognitionClient([
                    'version' => 'latest',
                    'region'  => 'us-east-1',
                    'credentials' => [
                    'key'    => 'AKIAJQUQU7NSU2GSX2GQ',
                    'secret' => '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'
                    ]
                    ]);

                $result = $rekognition->DetectModerationLabels([
                    'Image' => [
                    'S3Object' => [
                    'Bucket' => 'uploadwallpaper',
                    'Name' => $filename,
                    ],
                    ],
                    ]);
                $appeal = $result->get('ModerationLabels');
                $flag = 0;

                foreach ($appeal as $tag) {
                    $name = $tag['Name'];
                    if($name == 'Explicit Nudity' || $name == 'Suggestive')
                    {
                        $flag = 1;
                        break;
                    }
                }

                if($flag == 1)
                {
                    $s3->delete($filename);
                    $thumbnails->delete($filename);
                    $thumbnails->delete($thumb_filename);

                    return Response()->json([
                        'code' => -1
                        ]);
                }

                $result = $rekognition->DetectLabels([
                    'Image' => [
                    'S3Object' => [
                    'Bucket' => 'gettestimage',
                    'Name' => $filename,
                    ],
                    ],
                    'MaxLabels' => 123,
                    'MinConfidence' => 60,
                    ]);
                $tags = $result->get('Labels');

                $result = $rekognition->RecognizeCelebrities([
                    'Image' => [
                    'S3Object' => [
                    'Bucket' => 'gettestimage',
                    'Name' => $filename,
                    ]
                    ]
                    ]);

                $celebrity = $result->get('CelebrityFaces');
                $tags = array_merge($tags, $celebrity);

            } catch (Exception $e) {
                return Response()->json([
                    'error' => $e->getMessage()
                    ]);
            }

            // End Rekognition
            /**
             *  Store in Databse
             */
            $image = new ImageModel([
                's3_id' => $filename,
                'title' => $title,
                'upload_by' => $request->input('uid'),
                'upload_date' => Carbon::now(),
                'orig_height' => $orig_height,
                'orig_width' => $orig_width,
                'file_size' => $file_size
                ]);
            $image->save();

            $user = User::where('id', $request->input('uid'))->first();
            $user->uploads = $user->uploads + 1;
            $user->save();

            // Store in thumbnail
            $image_thumbnail = new Image_thumbnail([
                's3_id' => $filename,
                'image_id' => $image->id,
                'width' => 300,
                'height' => 300
                ]);
            $image_thumbnail->save();
            /**
             * Store tag
             */
            foreach ($tags as $tag) {
                $name = $tag['Name'];
                $origtag = Tag::wherename($name)->first();
                if($origtag)
                    $tag_id = $origtag->id;
                else{
                    // check if there is similar slug in database already
                    $slug = str_slug($name, "-");
                    $origslug = Tag::whereslug($slug)->first();
                    if($origslug) $slug.='0'; // if exists put 0 in the end

                    $tag = new Tag([
                        'name' => $name,
                        'slug' => $slug
                        ]);

                    $tag->save();
                    $tag_id = $tag->id;
                }
                $image_tag = new Image_tag([
                    'tag_id' => $tag_id,
                    'image_id' => $image->id
                    ]);
                $image_tag->save();
            }

            
            return Response()->json([
                'img_id' => $image->id
                ], 200);
        }
        return response()->json([
            'message' => "NO Image"
            ], 200);
    }

    /**
     * Upload a newly created resource in storage from URL.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadImageUrl(Request $request)
    {

        $url = $request -> input('url');

        if($request->input('uid')) {

            $photo = $request -> input('url');

            $originName = substr($url, strrpos($url, '/') + 1);
            $title = explode('.', $originName)[0];
            $title = ucwords(preg_replace('/[_-]/', ' ', $title));
            $file_size = getimagesize($url);


            $extension = pathinfo($url, PATHINFO_EXTENSION);
            $filename = time() . "." . $extension;

            $orig_width = $file_size[0];
            $orig_height = $file_size[1];
            $file_size = strlen(file_get_contents($photo));

            // set Image limitation(1024 * 768)
            if ($orig_width<1024 && $orig_height<768)
            {
                return response()->json([
                    'code' => 0
                    ], 200);
            }

            // save Image to the s3 Cache
            $s3 = Storage::disk('s3');
            $s3->put($filename, file_get_contents($photo), 'public');

            // $s3->put('uploads/'. $filename, fopen($photo, 'r+'), 'public');

            $thumbnails = Storage::disk('thumbnails');
            
            $manager = new ImageManager(array('driver' => 'imagick'));
            $thumb_photo = Image::make($photo)->fit(150, 150);
            $resource = $thumb_photo->stream()->detach();
            $thumbnails->put($filename, $resource, 'public');

            $thumb_photo = Image::make($photo)->fit(450, intval(450*$orig_height/$orig_width));
            $resource = $thumb_photo->stream()->detach();

            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $thumbnails->put($thumb_filename, $resource, 'public');

            /**
             *  Image Rekognition
             */
            try {
                $rekognition = new RekognitionClient([
                    'version' => 'latest',
                    'region'  => 'us-east-1',
                    'credentials' => [
                    'key'    => 'AKIAJQUQU7NSU2GSX2GQ',
                    'secret' => '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'
                    ]
                    ]);

                $result = $rekognition->DetectModerationLabels([
                    'Image' => [
                    'S3Object' => [
                    'Bucket' => 'uploadwallpaper',
                    'Name' => $filename,
                    ],
                    ],
                    ]);
                $appeal = $result->get('ModerationLabels');
                $flag = 0;

                foreach ($appeal as $tag) {
                    $name = $tag['Name'];
                    if($name == 'Explicit Nudity' || $name == 'Suggestive')
                    {
                        $flag = 1;
                        break;
                    }
                }

                if($flag == 1)
                {
                    $s3->delete($filename);
                    $thumbnails->delete($filename);
                    $thumbnails->delete($thumb_filename);

                    return Response()->json([
                        'code' => -1
                        ]);
                }

                $result = $rekognition->DetectLabels([
                    'Image' => [
                    'S3Object' => [
                    'Bucket' => 'gettestimage',
                    'Name' => $filename,
                    ],
                    ],
                    'MaxLabels' => 123,
                    'MinConfidence' => 75,
                    ]);
                $tags = $result->get('Labels');

                $result = $rekognition->RecognizeCelebrities([
                    'Image' => [
                    'S3Object' => [
                    'Bucket' => 'gettestimage',
                    'Name' => $filename,
                    ]
                    ]
                    ]);

                $celebrity = $result->get('CelebrityFaces');
                $tags = array_merge($tags, $celebrity);


            } catch (Exception $e) {
                return Response()->json([
                    'error' => $e->getMessage()
                    ]);
            }

            // End Rekognition
            /**
             *  Store in Databse
             */
            $image = new ImageModel([
                's3_id' => $filename,
                'title' => $title,
                'upload_by' => $request->input('uid'),
                'upload_date' => Carbon::now(),
                'orig_height' => $orig_height,
                'orig_width' => $orig_width,
                'file_size' => $file_size
                ]);
            $image->save();

            $user = User::where('id', $request->input('uid'))->first();
            $user->uploads = $user->uploads + 1;
            $user->save();

            // Store in thumbnail
            $image_thumbnail = new Image_thumbnail([
                's3_id' => $filename,
                'image_id' => $image->id,
                'width' => 300,
                'height' => 300
                ]);
            $image_thumbnail->save();
            /**
             * Store tag
             */
            foreach ($tags as $tag) {
                $name = $tag['Name'];
                $origtag = Tag::wherename($name)->first();
                if($origtag)
                    $tag_id = $origtag->id;
                else{
                    // check if there is similar slug in database already
                    $slug = str_slug($name, "-");
                    $origslug = Tag::whereslug($slug)->first();
                    if($origslug) $slug.='0'; // if exists put 0 in the end

                    $tag = new Tag([
                        'slug' => $slug,
                        'name' => $name
                        ]);
                    $tag->save();
                    $tag_id = $tag->id;
                }
                $image_tag = new Image_tag([
                    'tag_id' => $tag_id,
                    'image_id' => $image->id
                    ]);
                $image_tag->save();
            }
            return Response()->json([
                'img_id' => $image->id
                ], 200);
        }
        return response()->json([
            'message' => "NO Image"
            ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        // get parameters from request
        $image_id = $request->input('image_id');
        $width = $request->input('width');
        $height = $request->input('height');

        // check if Image is available
        $image = ImageModel::find($image_id);
        if(!$image)
        {
            return Response()->json([
            'code' => -1
            ]);
        }

        // s3 bucket variables
        $s3 = Storage::disk('s3');
        $cache_s3 = Storage::disk('thumbnails');

        // capture s3 cache image
        $index = strpos($image->s3_id, ".");
        
        // create session expired after 20 min
        $time = intval($image->s3_cached);
        $cache_url = substr_replace($image->s3_id, "-".$width."x".$height."-DesktopNexus", $index, 0);

        // check if time is expired
        if(!$cache_s3->has($cache_url)) 
        {
            // recreate cache file
            $url = $s3->url($image->s3_id); 
            $cache_photo = Image::make($url)->fit($width, $height);
            $resource = $cache_photo->stream()->detach();
            $cache_s3->put($cache_url, $resource, [ 'visibility' => 'public', 'Expires' => '+20 minutes' ]);
        }

        // create signed URL
        $client = $cache_s3->getDriver()->getAdapter()->getClient();
        $expiry = "+1 minutes";

        $command = $client->getCommand('GetObject', [
            'Bucket' => \Config::get('filesystems.disks.thumbnails.bucket'),
            'Key'    => $cache_url
            ]);

        $request = $client->createPresignedRequest($command, $expiry);
        // customized thumbnail url
        $filename = substr_replace($image->s3_id, "-bigthumbnail", $index, 0);
        $image->caches3 = (string) $request->getUri();
        $image->thumbnail = $filename;
        $image->now = $this->now($image->upload_date);

        $image_tag = ImageModel::join('image_tags','images.id','=','image_tags.image_id');
        $tags = $image_tag->join('tags','image_tags.tag_id','=','tags.id');
        $result = $tags->where('images.id', $image_id)->get(['name']);

        $c_group = DB::table('group_image')
        ->join('groups', 'group_image.group_id', '=', 'groups.name')
        ->where('group_image.image_id', $image_id)
        ->select('groups.name as name','avatar as s3_id')->get();

        // Get recently uploaded image
        $recent_group = ImageModel::join('group_image','group_image.image_id','=','images.id')
                        ->select('group_image.group_id')->where('images.id', $image_id)->get();
        
        // Get popular in each images
        $arr = [];
        foreach ($recent_group as $idx) {
            $query = ImageModel::join('group_image','group_image.image_id','=','images.id')
                                ->where('group_image.group_id', $idx->group_id)
                                ->select('images.*','group_image.group_id')
                                ->orderBy('images.downloads')->limit(5)->get();
            $temp['key']=$idx->group_id;
            $temp['object']=$query;
            array_push($arr, $temp);
        }                     

        $recent = ImageModel::join('group_image','group_image.image_id','=','images.id')
                    ->join('groups','groups.name','=','group_image.group_id')
                    ->select('images.id',DB::raw('group_concat(groups.name) as list'))->groupBy('images.id')->orderBy('images.created_at')->limit(2)->get();

        return Response()->json([
            'image' => $image,
            'tags' => $result,
            'cgroup' => $c_group,
            'recent' => $arr
            ]);
    }

    /**
     * Get Wallpaper Information
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getWallpaper(Request $request)
    {
        // get parameters from request
        $image_id = $request->input('image_id');
        $width = $request->input('width');
        $height = $request->input('height');
        $ip = $request->input('ip');

        // check if image with ID is available
        $image = ImageModel::find($image_id);
        if(!$image)
        {
            return Response()->json([
            'code' => -1
            ], 404);    // return 404 error Image not found
        }

        // if image is available, register with new ip
        $imgcomment = new Image_View([
            'image_id' => $image_id,
            'ip_address' => $ip
            ]);
        $imgcomment->save();

        // s3 bucket variables
        $s3 = Storage::disk('s3');
        $cache_s3 = Storage::disk('thumbnails');

        /**
        **
            render Width * Height pre-cached Image based on resolution of the browser screen
        **
        **/            
        // capture s3 cache image
        $index = strpos($image->s3_id, ".");
        // create cached Image        
        $cache_url = substr_replace($image->s3_id, "-".$width."x".$height."-DesktopNexus", $index, 0);
        $url = $s3->url($image->s3_id);

        // check if time is expired
        if(!$cache_s3->has($cache_url)) 
        {
            // recreate cache file
            $url = $s3->url($image->s3_id);
            $cache_photo = Image::make($url)->fit($width, $height);
            $resource = $cache_photo->stream()->detach();
            $cache_s3->put($cache_url, $resource, [ 'visibility' => 'public', 'Expires' => '+20 minutes' ]);
        }

        // create signed URL
        $client = $cache_s3->getDriver()->getAdapter()->getClient();
        $expiry = "+1 minutes";

        $command = $client->getCommand('GetObject', [
            'Bucket' => \Config::get('filesystems.disks.thumbnails.bucket'),
            'Key'    => $cache_url
            ]);

        $request = $client->createPresignedRequest($command, $expiry);

        /**
        **
            Pops up required information of wallpaper (Image, Tags, Recent Groups)
        **
        **/            
        // customized thumbnail url
        $filename = substr_replace($image->s3_id, "-bigthumbnail", $index, 0);
        $image->caches3 = (string) $request->getUri();  // cached Image URL for Download
        $image->thumbnail = $filename;  // thumbnail for Wallpaper
        $image->now = $this->now($image->upload_date);  // [ uploaded ** ago ]

        // tags
        $result = $this->getTags($image_id);

        // groups of current wallpaper
        $c_group = DB::table('group_image')
        ->leftJoin('groups', 'group_image.group_id', '=', 'groups.name')
        ->where('group_image.image_id', $image_id)
        ->select('groups.name as name','avatar as s3_id','groups.slug')->get();

        // Get recently uploaded image
        $recent_group = ImageModel::join('group_image','group_image.image_id','=','images.id')
                        ->select('group_image.group_id')->where('images.id', $image_id)->get();
        
        // Get popular in each images (5)
        $arr = [];
        foreach ($recent_group as $idx) {
            $query = ImageModel::join('group_image','group_image.image_id','=','images.id')
                                ->where('group_image.group_id', $idx->group_id)
                                ->select('images.*','group_image.group_id')
                                ->orderBy('images.downloads')->limit(5)->get();
            $slug = Group::where('name', $idx->group_id)
                    ->select('slug')->first();
            $temp['key'] = $idx->group_id;
            $temp['slug'] = $slug->slug; 
            $temp['object'] = $query;
            array_push($arr, $temp);
        }                     

        //  get Uploader
        $uploader = User::where('id', $image->upload_by)->first();
        if (!$uploader)
        {
            return Response()->json([
                'code' => -1
            ], 500);    // when uploader is not available, 500 internal server
        }

        /*
        **
            get Comments
        **
        */
        $imgcomments = Image_comment::where('image_id', $image_id)->orderBy('created_at', 'desc')->get();
        foreach ($imgcomments as $imgcomment) {
            $imgcomment->now = $this->now($imgcomment->created_at);
            // get Avatar_info & Banner_info from User-id
            $users = Users::where('id', $imgcomment->author_id)->first();
            // If Author existing
            if($users)
                $imgcomment->author_img = $users->avatar;
            else
                $imgcomment->author_img = "";
        }
        
        /*
        **
            get Available groups followed by Uploader
        **
        */
        $group = Group::join('group_follows','groups.id','=','group_follows.group_id')
                        ->where('group_follows.follower_id', $uploader->id)
                        ->select("name as name",'avatar as s3_id','groups.slug')
                        ->get();

        return Response()->json([
            'image' => $image,
            'uploader' => $uploader,
            'comment' => $imgcomments,
            'tags' => $result,
            'c_group' => $c_group,
            'a_group' => $group,
            'popular' => $arr
            ]);
    }

    /**
     *  Show the Date format
     */
    public function now($upload_date)
    {
        // Years
        $now = intval((strtotime(date('Y-m-d H:i:s'))-strtotime($upload_date))/(3600*24*365)) . " ";
        if($now == 1 ) return $now . "year ago";
        if($now > 1) return $now . "years ago";
        // Months
        $now = intval((strtotime(date('Y-m-d H:i:s'))-strtotime($upload_date))/(3600*24*30)) . " ";
        if($now == 1) return $now . "month ago";
        if($now > 1) return $now . "months ago";
        //  Weeks
        $now = intval((strtotime(date('Y-m-d H:i:s'))-strtotime($upload_date))/(3600*24*7)) . " ";
        if($now == 1) return $now . "week ago";
        if($now > 1) return $now . "weeks ago";
        // Days
        $now = intval((strtotime(date('Y-m-d H:i:s'))-strtotime($upload_date))/(3600*24)) . " ";
        if($now == 1) return $now . "day ago";
        if($now > 1) return $now . "days ago";
        // hours
        $now = intval((strtotime(date('Y-m-d H:i:s'))-strtotime($upload_date))/(3600)) . " ";
        if($now == 1) return $now . "hour ago";
        if($now > 1) return $now . "hours ago";
        // minutes
        $now = intval((strtotime(date('Y-m-d H:i:s'))-strtotime($upload_date))/(60)) . " ";
        if($now > 1) return $now . "minutes ago";
        else return "1 minute ago";
    }

    /**
     *  Update the like of image
     */
    public function like($id, $user_id)
    {
        $image = ImageModel::find($id);
        $user_like = User_like::where('image_id', $id)->where('user_id', $user_id)->first();
        // if already like by user
        if($user_like){
            $user_like->delete();
            if($image->likes > 0){
                $image->likes -= 1;
                $image->save();
            }
            return Response()->json([
                "result" => $image->likes,
                "like" => 0
                ], 200);
        }
        // if not, like the image
        $image->likes += 1;
        $image->save();
        $new_like = new User_like([
            'user_id' => $user_id,
            'image_id' => $id
            ]);
        $new_like->save();
        return Response()->json([
            "result" => $image->likes,
            "like" => 1
            ], 200);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $s3 = Storage::disk('s3');
        $s3_thumbnails = Storage::disk('thumbnails');

        $image = ImageModel::find($id);
        if($s3->has('uploads/' . $image->s3_id))
            $s3->delete('uploads/' . $image->s3_id);
        // return Response()->json([
        //     "image" => $image->s3_id
        //     ]);
        $image->delete();
        $image_comment = Image_comment::where('image_id', $id)->first();
        if($image_comment)
            $image_comment->delete();
        $image_tag = Image_tag::where('image_id', $id)->first();
        if($image_tag)
            $image_tag->delete();

        $image_thumbnail = Image_thumbnail::where('image_id', $id)->first();
        if($image_thumbnail) {
            if($s3_thumbnails->has($image_thumbnail->s3_id))
                $s3_thumbnails->delete($image_thumbnail->s3_id);
            $image_thumbnail->delete();
        }
        return Response()->json([
            "result" => "Successful"
            ], 200);
    }

    /**
     * Comment Section
     */
    public function newComment(Request $request)
    {
        $author_id = $request->input('author');

        $author = User::find($author_id);
        if (!$author)
        {
            return Response()->json([
                'code' => 0
            ], 500);    
        }

        $imgcomment = new Image_comment([
            'image_id' => $request->input('image_id'),
            'author_id' => $author_id,
            'parent_id' => $request->input('parent_id'),
            'content' => $request->input('newcmt'),
            'sticker' => $request->input('stick'),
            'author_img' => $author->avatar,
            'author_name' => $author->username
            ]);
        $imgcomment->save();
        $imgcomment->now = $this->now($imgcomment->created_at);
        return Response()->json([
            'newcomment' => $imgcomment
            ]);
    }

    public function getComment($id)
    {
        $img = ImageModel::where('id', $id)->first();
        if(!$img)
        {
            return Response()->json([
                'code' => -1
            ]);    
        }

        $imgcomments = Image_comment::where('image_id', $id)->orderBy('created_at', 'desc')->get();
        foreach ($imgcomments as $imgcomment) {
            $imgcomment->now = $this->now($imgcomment->created_at);
            // get Avatar_info & Banner_info from User-id
            $users = Users::where('id', $imgcomment->author_id)->first();
            // If Author existing
            if($users)
                $imgcomment->author_img = $users->avatar;
            else
                $imgcomment->author_img = "";
        }
        
        return Response()->json([
            'comments' => $imgcomments
            ]);
    }

    /*
    **
        invite social share via email
    **
    */
    public function invite(Request $request)
    {
        $this->validate($request, [
            'email' => 'required'
            ]);
        $email = $request->input('email');
        $wallpaper_id = $request->input('id');

        $url = env('HOST') . "wallpaper/" . $wallpaper_id;
        // create invite template
        $validateEmail = new Invite();
        // message body
        $content = $request->input('fullname');
        $content.= " invites you to <a href='".$url."'>DesktopNexus.com</a>";
        // send email
        $validateEmail->to($email, $request->input('fullname'))
        ->setViewData([
            'FNAME' => $request->input('fullname'),
            'CONTENT' => $content
            ]);
        Mail::queue($validateEmail);
        return Response()->json([
            "result" => "Successful. Please Check your Email."
            ], 200);
    }

    /**
     *  save Fav tree
     */
    public function saveFavtree(Request $request)
    {
       // get userId, fav data
        $fav_data = $request->input('favData');
        $user_id = $request->input('user_id');

        // remove all fav tree data of user
        Folder::where('user_id', $user_id)->delete();

        // reconstruct tree
        if($fav_data){
            foreach ($fav_data as $favdata) {
                $folder = new Folder([
                  'id' => $favdata['id'],
                  'user_id' => $user_id,
                  'text' => $favdata['text'],
                  'parent' => $favdata['parent']
                  ]);
                $folder->save();
            }
        }

        return Response()->json([
            'result' => 1
        ]);
    }

    /**
     *  Update the favorite
     */
    public function favorite(Request $request, $img_id)
    {
        // get active & favorite Nodes
        $active_node = $request->input('active_node');
        $user_id = $request->input('user_id');

        $image_model = ImageModel::find($img_id);

        $folder_id = '';
        $favImage = FavImage::where('user_id', $user_id)->where('image_id', $img_id)->first();

        // if image with same id exists, return
        if($favImage)
        {
            // change the favorite
            $favImage->folder_id = $active_node;
            $favImage->save();

            return Response()->json([
                'result' => $image_model->favorites
                ]);
        }       

        // save to the user fav image
        $favImage = new FavImage([
            'folder_id' => $active_node,
            'image_id' => $img_id,
            'user_id' => $user_id
            ]);
        $favImage->save();

        // increase favorite
        $image_model->favorites += 1;
        $image_model->save();

        return Response()->json([
            'result' => $image_model->favorites
        ]);
    }

    /**
     *  Update the report of the image/wallpaper
     */
    public function report(Request $request)
    {
        // request information variable
        $reqImage = $request->input('image_id');
        $category = $request->input('category');
        $content = $request->input('content');

        // report Object create 
        $report = new Report([
            'image_id' => $reqImage,
            'category' => $category,
            'content' => $content
            ]);
        // database save
        $report->save();

        // return JSON data
        return Response()->json([
          'result' => 1
          ], 200);
    }

    public function getFavInfo($img_id, $user_id)
    {
        $img = ImageModel::where('id', $img_id)->first();
        if(!$img)
        {
            return Response()->json([
                'code' => -1
            ]);    
        }

        $favImage = FavImage::where('image_id', $img_id)->where('user_id', $user_id)->first();
        $likeImage = User_like::where('image_id', $img_id)->where('user_id', $user_id)->first();
        $socialImage = User_Social::where('image_id', $img_id)->where('user_id', $user_id)->first();
        
        $folders = DB::table('user_fav_folders')->where('user_id', $user_id)->get(['id', 'text', 'parent']);
        $fav = 0;
        $like = 0;
        $social = 0;
        $active = "";

        if($favImage){
            $fav = 1;
            $active = $favImage->folder_id;
        }
        if($socialImage) $social = 1;
        if($likeImage) $like = 1;

        $is_ban = 0;
        $user = User::where('id', $img->upload_by)->first();
        $user_ignore = User_Ignore::where('user_id', $user->id)->where('banned_id', $user_id)->get(); 
        if(count($user_ignore) != 0) $is_ban = 1;

        return Response()->json([
        'active' => $active, 
        'fav' => $fav,
        'like' => $like,
        'social' => $social,
        'folders' => $folders,
        'is_ban' => $is_ban
        ], 201);
    }

    public function isFollowGallery(Request $request)
    {
        $gallery_name = $request->input('gallery');
        $user_id = $request->input('uid');
        $gallery = Gallery::where('slug', $gallery_name)->first();
        
        // gallery not exist
        if(!$gallery) 
        {
            return Response()->json([
                'result' => 0
            ], 500);
        }

        $gallery_follow = Gallery_Follows::where('gallery_id', $gallery->id)
                ->where('follower_id', $user_id)
                ->first();
        
        // if already followed by user                
        if($gallery_follow)
        {
            return Response()->json([
                'result' => 1
                ], 200);
        }

        return Response()->json([
            'result' => 0
            ], 200);   
    }

    public function isFollowGroup(Request $request)
    {
        $group_name = $request->input('group');
        $user_id = $request->input('uid');
        $group = Group::where('slug', $group_name)->first();
        
        // group not exist
        if(!$group) 
        {
            return Response()->json([
                'result' => 0
            ], 500);
        }

        $group_follow = Group_Follows::where('group_id', $group->id)
                ->where('follower_id', $user_id)
                ->first();

        // if already followed by user                
        if($group_follow)
        {
            return Response()->json([
                'result' => 1
                ], 200);
        }

        return Response()->json([
            'result' => 0
        ], 200);  
    }

    public function delFavImage($img_id, $user_id)
    {
        $imageModel = ImageModel::find($img_id);
        $favImage = FavImage::where('image_id', $img_id)->where('user_id', $user_id)->first();
        if($favImage) {
            $favImage->delete();
            if($imageModel->favorites > 0) $imageModel->favorites -= 1;
            $imageModel->save();
            return Response()->json([
            'result' => $imageModel->favorites
            ], 200);
        }
        else {
            return Response()->json([
            'result' => $imageModel->favorites
            ], 200);
        }
    }

    public function edit(Request $request)
    {
        $reqImage = $request->input('image');
        $reqTags = $request->input('tags');
        $reqImagGroups = $request->input('imgGroup');
        $image = ImageModel::find($reqImage['id']);
        $image->title = $reqImage['title'];
        $image->save();

        // remove all tags for image_id
        $image_tag = Image_tag::where('image_id', $image->id)->delete();

        // reset image tag
        foreach ($reqTags as $reqtag) {
            $origtag = Tag::wherename($reqtag)->first();

            $tag_id = $origtag['id'];
            if(is_null($reqtag)) break;
            if(!$origtag)
            {
                // check if there is similar slug in database already
                $slug = str_slug($reqtag, "-");
                $origslug = Tag::whereslug($slug)->first();
                if($origslug) $slug.='0'; // if exists put 0 in the end

                $tag = new Tag([
                    'slug' => $slug,
                    'name' => $reqtag
                    ]);
                $tag->save();
                $tag_id = $tag->id;
            }
            $image_tag = Image_tag::where('tag_id', $tag_id)->where('image_id', $image->id)->first();
            if($image_tag) continue;
            $image_tag = new Image_tag([
                'tag_id' => $tag_id,
                'image_id' => $image->id
                ]);
            $image_tag->save();
        }

        // remove all groups from Group
        Group_image::where('image_id', $image->id)->delete();

        foreach ($reqImagGroups as $reqImagGroup) {
            $imageGroup = Group_image::where('group_id', $reqImagGroup['name'])->where('image_id', $image->id)->first();
            if($imageGroup) continue;
            $imageGroup = new Group_image([
                'group_id' => $reqImagGroup['name'],
                'image_id' => $reqImage['id']
                ]);
            $imageGroup->save();
        }

        // Get recently uploaded image
        $recent_group = ImageModel::join('group_image','group_image.image_id','=','images.id')
                        ->select('group_image.group_id')->where('images.id', $image->id)->get();
        
        // Get popular in each images (5)
        $arr = [];
        foreach ($recent_group as $idx) {
            $query = ImageModel::join('group_image','group_image.image_id','=','images.id')
                                ->where('group_image.group_id', $idx->group_id)
                                ->select('images.*','group_image.group_id')
                                ->orderBy('images.downloads')->limit(5)->get();
            $temp['key']=$idx->group_id;
            $temp['object']=$query;
            array_push($arr, $temp);
        }   

        $taglist = $this->getTags($image->id);

        return Response()->json([
            "result" => 1,
            "tag" => $taglist,
            'popular' => $arr
            ]);
    }

    /*
    **
        return tags list based on image id
    **
    */
    public function getTags($image_id)
    {
        // tags
        $image_tag = ImageModel::join('image_tags','images.id','=','image_tags.image_id');
        $tags = $image_tag->join('tags','image_tags.tag_id','=','tags.id');
        $result = $tags->where('images.id', $image_id)->get(['name','slug']);
        return $result;
    }

    /*
    **
        get general information - wallpaper, user, overall type
    **
    */
    public function getGeneralInfo(Request $request)
    {
        $count = env('RECENTLY_SHARE');
        $image = count(ImageModel::All());
        $member = count(Users::All());
        $likes = count(User_like::All());
        $fav = count(FavImage::All());
        $follow = count(Group_Follows::All()) + count(Gallery_Follows::All());
        $feed = DB::table('user_social as tb1')
                ->leftJoin('images', 'tb1.image_id','images.id')
                ->where('tb1.id', DB::raw('(select id from user_social tb2 where tb1.image_id = tb2.image_id order by created_at asc limit 1)'))
                ->orderBy('images.created_at', 'desc')
                ->limit($count)
                ->get();
        $gallery = Gallery::All();
        $g_popular = Gallery::leftJoin('gallery_follows', 'galleries.id', '=' ,'gallery_follows.gallery_id')
                ->select('galleries.*', DB::raw('count(gallery_follows.id) as item_count'), DB::raw("'1' as rtype"))
                ->groupBy('galleries.id')
                ->orderBy('item_count','desc')
                ->limit(10)
                ->get();
        $t_popular = Group::leftJoin('group_follows', 'groups.id', '=' ,'group_follows.group_id')
                ->select('groups.*', DB::raw('count(group_follows.id) as item_count'), DB::raw("'2' as rtype"))
                ->groupBy('groups.id')
                ->orderBy('item_count','desc')
                ->limit(10)
                ->get();

        // put Sorts on Collection                  
        $all = collect();
        foreach ($g_popular as $item)
        {
            $all->push($item);
        }
        foreach ($t_popular as $item){
            $all->push($item);
        }

        //DESC based on created date
        $all = array_reverse(array_sort($all, function ($value) {
            return $value['item_count'];
        }));

        $all = array_slice($all, 0, 6);           
        return Response()->json([
            "image" => $image,
            "member" => $member,
            "likes" => $likes,
            "fav" => $fav,
            "follow" => $follow,
            "feed" => $feed,
            "gallery" => $gallery,
            "popular" => $all
            ], 200);
    }

    /*
    **
        get header information - image count, gallery, groups on logged authrization
    **
    */
    public function getHeader(Request $request)
    {
        $image = count(ImageModel::All());
        $online = 0;
        $galleries = Gallery::all();
        $user = $request->input('user_id');

        $upload = ImageModel::where('upload_by', $user)
                  ->get()
                  ->count();

        $favorite = FavImage::where('user_id', $user)
                    ->get()
                    ->count();

        $recent_visit = DB::table('log as tb1')
                ->where('tb1.id', DB::raw('(select id from log tb2 where tb1.ip_address = tb2.ip_address AND tb1.path = tb2.path order by tb2.created_at desc limit 1)'))
                ->where('tb1.ip_address', $request->input('ip'))
                ->orderBy('tb1.created_at', 'desc')
                ->limit(5)
                ->get();

        // user role
        $role_query = DB::table('role_user')
                ->leftJoin('roles', 'roles.id','=','role_user.role_id')
                ->where('role_user.user_id', $user)
                ->first();

        $role_flag = 0;
        if($role_query)
        {
            if($role_query->name == 'moderator' || $role_query->name == 'admin') $role_flag = 1;
        }

        return Response()->json([
            "image" => $image,
            "online" => $online,
            "gallery" => $galleries,
            "upload" => $upload,
            "favorite" => $favorite,
            "recent" => $recent_visit,
            "role_flag" => $role_flag
            ], 200);
    }

    public function sendDiamond(Request $request)
    {
        $sender_id = $request->input('user_id');
        $receiver_id = $request->input('uploader_id');
        $diamond = $request->input('amount');

        $sender = User::find($sender_id);
        $receiver = User::find($receiver_id);

            // if receiver not exist in database
        if(!$receiver)
        {
            return Response()->json([
                'result' => 0
                ], 201);    
        }

        $sender->diamond -= $diamond;
        $receiver->diamond += $diamond;
        $sender->save();
        $receiver->save();

        return Response()->json([
            'sender' => $sender,
            'receiver' => $receiver,
            'result' => 1
            ], 200); 
    }

    public function downloadWallpaper(Request $request)
    {

        $image_id = $request->input('img_id');
        $ip_address = $request->input('ip');

        // check if same user downloads the same ip
        $download = Image_Download::where('ip_address', $ip_address)
                                 ->where('image_id', $image_id)
                                 ->first();
        
        if($download)
        {
            // check the session time
            $now = intval((strtotime(date('Y-m-d H:i:s'))-strtotime($download->created_at))/(3600*24));
            if($now >= 1)
            {
                $download->created_at = date('Y-m-d H:i:s');
                $download->save();
            }
            else
                return Response()->json([
                    'result' => 0
                ], 200);
        }
        else
        {
            $download = new Image_Download([
                'ip_address' => $ip_address,
                'image_id' => $image_id
            ]);

            $download->save();
        }

        // increase the download
        $image = ImageModel::where('id', $image_id)->first();
        $image->downloads = $image->downloads + 1;
        $image->save();

        return Response()->json([
            'result' => 1
            ], 200);
    }

    /*
    **
        return encrypted token based on @image_id, @width, @height
    **
    */
    public function downloadToken(Request $request)
    {
        $image_id = $request->input('img_id');
        $width = $request->input('width');
        $height = $request->input('height');
        $time = Carbon::now();

        $token = ([
            'image_id'=> $image_id,
            'width' => $width,
            'height' => $height,
            'time' => $time
        ]);

        $encrypted = Crypt::encrypt(json_encode($token));
        return Response()->json([
            'result' => $encrypted
            ], 200);
    }

    /*
    **
        return image as content-dispatch and download them as attachment
    **
    */
    public function downloadImage(Request $request)
    {
        // crypted Token
        $encrypt = $request->input('X-Amz-Signature');
        $decrypt = Crypt::decrypt($encrypt);
        // decrypt parameter
        $data = json_decode($decrypt);
        $image_id = $data->image_id;
        $width = $data->width;
        $height = $data->height;
        $time = $data->time;

        // create session expired after 15 min
        if(intval((strtotime(date('Y-m-d H:i:s'))-strtotime($time->date))/(60))>15)
        {
            return Response()->json([
                'code' => -1
            ], 400);
        }

        // get Image object
        $image = ImageModel::where('id', $image_id)->first();

        // s3 bucket variables
        $s3 = Storage::disk('s3');
        $cache_s3 = Storage::disk('thumbnails');

        // capture s3 cache image
        $index = strpos($image->s3_id, ".");
        $cache_url = substr_replace($image->s3_id, "-".$width."x".$height."-DesktopNexus", $index, 0);
        $ext = preg_replace('/^.*\.([^.]+)$/D', '$1', $image->s3_id);
        // get s3 bucket object
        $file = $cache_s3->get($cache_url);

        return response($file, 200)->header('Content-Transfer-Encoding', 'binary')->header('Content-Type', 'application/x-unknown')->header('Content-Disposition', 'attachment; filename='.$cache_url);
    }

    public function socialUpdate(Request $request)
    {

        $image_id = $request->input('img_id');
        $uid = $request->input('user_id');
        $ip_address = $request->input('ip');

        // check if same user downloads the same ip
        $download = User_Social::where('ip_address', $ip_address)
                                 ->where('user_id', $uid)
                                 ->where('image_id', $image_id)
                                 ->first();
        
        if($download)                                 
        {
            // check the session time
            $now = intval((strtotime(date('Y-m-d H:i:s'))-strtotime($download->created_at))/(3600*24));
            if($now >= 1)
            {
                $download->created_at = date('Y-m-d H:i:s');
                $download->save();
            }
            else 
                return Response()->json([
                    'result' => 0
                ], 200);
        }
        else
        {
            $download = new User_Social([
                'ip_address' => $ip_address,
                'user_id' => $uid,
                'image_id' => $image_id
            ]);

            $download->save();
        }

        // increase the download
        $image = ImageModel::where('id', $image_id)->first();
        $image->shares = $image->shares + 1;
        $image->save();

        return Response()->json([
            'result' => 1
            ], 200);
    }

    public function getGalleryList() {
        $galleries = Gallery::all();
        return Response()->json([
            'result' => $galleries
            ], 200);
    }

    public function getGallery()
    {
        $galleries = Gallery::all();
        $groups = DB::table('groups')
        ->join('gallery_groups', 'groups.id', '=', 'gallery_groups.group_id')
        ->join('galleries', 'galleries.id', '=', 'gallery_groups.gallery_id')
        ->where('groups.status', '=', 'active')
        ->select('groups.*','galleries.id as gallery_id')->get();

        $tags = DB::table('tags')
        ->join('image_tags', 'tags.id', '=', 'image_tags.tag_id')
        ->join('images', 'images.id', '=', 'image_tags.image_id')
        ->select('tags.name','tags.slug',DB::raw('sum(images.downloads) as sum'))->groupBy('tags.name')->orderBy('tags.created_at', 'desc')->get();

        $max = 0;
        $min = 0;
        foreach ($tags as $key) {
            $max = max($max, $key->sum);
            $min = min($min, $key->sum);
        }

        return Response()->json([
            "galleries" => $galleries,
            "groups" => $groups,
            "tags" => $tags,
            "max" => $max,
            "min" => $min
            ]);
    }

    public function followGallery(Request $request)
    {
        $gallery_name = $request->input('gallery');
        $user_id = $request->input('user_id');
        $gallery = Gallery::where('name', $gallery_name)->first();
        
        // gallery not exist
        if(!$gallery) 
        {
            return Response()->json([
                'result' => -1
            ], 500);
        }

        $gallery_follow = Gallery_Follows::where('gallery_id', $gallery->id)
                ->where('follower_id', $user_id)
                ->first();
        
        // if already followed by user                
        if($gallery_follow)
        {
            $gallery_follow->delete();
            return Response()->json([
                'result' => 0
                ], 200);
        }
        // if not followed, following
        else
        {
            $gallery_follow = DB::table('gallery_follows')->insert([
                'gallery_id' => $gallery->id,
                'follower_id' => $user_id
            ]);
        }

        return Response()->json([
            'result' => 1
            ], 200);
    }

    public function followGroup(Request $request)
    {
        $group_name = $request->input('group');
        $user_id = $request->input('user_id');
        $group = Group::where('name', $group_name)->first();
        
        // gallery not exist
        if(!$group) 
        {
            return Response()->json([
                'result' => -1
            ], 500);
        }

        $group_follow = Group_Follows::where('group_id', $group->id)
                ->where('follower_id', $user_id)
                ->first();

        // if already followed by user                
        if($group_follow)
        {
            $group_follow->delete();
            return Response()->json([
                'result' => 0
                ], 200);
        }
        // if not followed, following
        else
        {
            $group_follow = DB::table('group_follows')->insert([
                'group_id' => $group->id,
                'follower_id' => $user_id
            ]);

        }

        return Response()->json([
            'result' => 1
        ], 200);
    }

    public function getCategory(Request $request, $name)
    {

        //$load_per_page = env('GALLERY_PER_PAGE');
        $load_per_page = 12;

        $gallery = Gallery::whereslug($name)->first();

        // check if gallery name is existing
        if (!$gallery)
        {
            return Response()->json([
            'code' => -1
            ], 404);    // return 404 error Image not found
        }

        // log the visit
        $log = new Log([
                'ip_address' => $request->input('ip'),
                'path' => 'gallery/' . $name,
                'refer' => $gallery->name
                ]);
        if($request->input('ip')!="") $log->save();
        
        $name = $gallery->name;
      
        $gallery = Gallery::leftJoin('gallery_follows', 'galleries.id', '=' ,'gallery_follows.gallery_id')
        ->where('galleries.name', $name)
        ->select('galleries.*', DB::raw('count(gallery_follows.id) as item_count'))
        ->first();

        $groups = DB::table('groups')
        ->leftJoin('gallery_groups', 'groups.id', '=', 'gallery_groups.group_id')
        ->leftJoin('galleries', 'galleries.id', '=', 'gallery_groups.gallery_id')
        ->leftJoin('group_image', 'group_image.group_id', '=', 'groups.name')
        ->leftJoin('images', 'images.id', '=', 'group_image.image_id')
        ->where('groups.status', '=', 'active')
        ->where('galleries.name','=', $name)
        ->groupBy('groups.id')
        ->select('groups.*', DB::raw('count(images.downloads) as image_count'))
        ->get();

        $images = DB::table('groups')
        ->join('gallery_groups', 'groups.id', '=', 'gallery_groups.group_id')
        ->join('galleries', 'galleries.id', '=', 'gallery_groups.gallery_id')
        ->join('group_image', 'group_image.group_id', '=', 'groups.name')
        ->join('images', 'images.id', '=', 'group_image.image_id')
        ->join('users', 'users.id', '=', 'images.upload_by')
        ->where('groups.status', '=', 'active')
        ->where('galleries.name','=', $name);
        
        $image_length = $images
        ->select('images.id as id')
        ->distinct('images.id')
        ->get()->count()
        ;

        $images = $images
        ->select('images.*','users.firstname','users.lastname','users.avatar', 'users.username')
        ->distinct('images.id')
        ->orderBy('images.updated_at','desc')
        ->offset(0)
        ->limit($load_per_page)
        ->get();

        $tags = DB::table('tags')
        ->join('image_tags', 'tags.id', '=', 'image_tags.tag_id')
        ->join('images', 'images.id', '=', 'image_tags.image_id')
        ->select('tags.name','tags.slug',DB::raw('sum(images.downloads) as sum'))->groupBy('tags.name')->orderBy('tags.created_at', 'desc')->get();
        
        $max = 0;
        $min = 0;
        foreach ($tags as $key) {
            $max = max($max, $key->sum);
            $min = min($min, $key->sum);
        }

        // refresh the thumbnail url from original to refined
        $all = collect();
        foreach ($images as $item)
        {
            // get view
            $image_view = Image_View::where('image_id','=',$item->id)
                          ->get();
            // get favorite
            $image_fav = FavImage::where('image_id', '=', $item->id)
                         ->get();
            // get comment
            $image_comment = Image_comment::where('image_id', '=', $item->id)
                             ->get();
            $filename = $item->s3_id;
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item->s3_id = $thumb_filename;
            $item->now = $this->now($item->updated_at);
            $item->view = count($image_view);
            $item->favorites = count($image_fav);
            $item->comments = count($image_comment);
            $all->push($item);
        }

        // get recent log history
        $recent_visit = DB::table('log as tb1')
                ->where('tb1.id', DB::raw('(select id from log tb2 where tb1.ip_address = tb2.ip_address AND tb1.path = tb2.path order by tb2.created_at desc limit 1)'))
                ->where('tb1.ip_address', $request->input('ip'))
                ->orderBy('tb1.created_at', 'desc')
                ->limit(5)
                ->get();

        return Response()->json([
            "Hehe" => $load_per_page,
            "gallery" => $gallery,
            "groups" => $groups,
            "images" => $all,
            "length" => $image_length,
            "tags" => $tags,
            "min" => $min,
            "max" => $max,
            "recent" => $recent_visit
            ], 200);
    }

    public function getImage(Request $request)
    {

        //$load_per_page = env('GALLERY_PER_PAGE');
        $load_per_page = 12;

        $name = $request->input('group_name');
        $offset = $request->input('page');

        $gallery = $request->input('gallery');

        if ($name == '-1')
        {
            $images = DB::table('groups')
            ->join('gallery_groups', 'groups.id', '=', 'gallery_groups.group_id')
            ->join('galleries', 'galleries.id', '=', 'gallery_groups.gallery_id')
            ->join('group_image', 'group_image.group_id', '=', 'groups.name')
            ->join('images', 'images.id', '=', 'group_image.image_id')
            ->join('users', 'users.id', '=', 'images.upload_by')
            ->where('groups.status', '=', 'active')
            ->where('galleries.name','=', $gallery)
            ->select('images.*','users.firstname','users.lastname','users.avatar', 'images.updated_at as updated_at')
            ->distinct('images.id')
            ->orderBy('images.updated_at','desc')
            ->offset($offset * $load_per_page)
            ->limit($load_per_page)
            ->get();
        }
        else
        {
            $group = Group::wherename($name)->get()->first();
            $images = DB::table('groups')
            ->join('group_image', 'group_image.group_id', '=', 'groups.name')
            ->join('images', 'images.id', '=', 'group_image.image_id')
            ->join('users', 'users.id', '=', 'images.upload_by')
            ->where('groups.name', '=', $name)
            ->select('images.*', 'users.id as userid', 'users.*', 'images.id as id', 'images.updated_at as updated_at')
            ->distinct('images.id')
            ->orderBy('images.updated_at','desc')
            ->offset($offset * $load_per_page)
            ->limit($load_per_page)
            ->get();
        }
        
        // refresh the thumbnail url from original to refined
        $all = collect();
        foreach ($images as $item)
        {
            $filename = $item->s3_id;
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item->s3_id = $thumb_filename;
            $item->now = $this->now($item->updated_at);
            $all->push($item);
        }

        return Response()->json([
            'images' => $all
            ], 200);
    }

    public function onSearchTag($tag)
    {
        $images = DB::table('images')
        ->join('image_tags', 'images.id', '=', 'image_tags.image_id')
        ->join('tags', 'tags.id', '=', 'image_tags.tag_id')
        ->join('users', 'users.id', '=', 'images.upload_by')
        ->where('tags.name', $tag)
        ->select('images.id as imageid','images.*','users.id as userid', 'users.*')
        ->get();
        return Response()->json([
            'result' => $images
            ]);
    }

    public function createGroup(Request $request)
    {
        /* parameter input */
        $avatar = $request->input('avatar');
        $banner = $request->input('banner');
        $name = $request->input('name');
        $overview = $request->input('overview');
        $detail = $request->input('detail');
        $gallery = $request->input('gallery');
        $owner = $request->input('owner');
        $group = Group::where('name', $name)->first();
        if($group)
        {
            return Response()->json([
            'result' => 0
            ], 500);
        }
        /* database */
        $slug = str_slug($name, "-");
        $origslug = Tag::whereslug($slug)->first();
        if($origslug) $slug.='0'; // if exists put 0 in the end

        $group = new Group([
                'name' => $name,
                'slug' => $slug,
                'avatar' => $avatar,
                'cover_img' => $banner,
                'status' => 'active',
                'description' => $overview,
                'owner_id' => $owner,
                'gallery_id' => $gallery
                ]);
        $group->save();

        $gallery = new Gallery_group([
                'group_id' => $group->id,
                'gallery_id' => $gallery
            ]);
        $gallery->save();

        return Response()->json([
            'result' => $group
            ]);
    }

    public function updateGroup(Request $request)
    {
        /* parameter input */
        $avatar = $request->input('avatar');
        $banner = $request->input('banner');
        $name = $request->input('name');
        $overview = $request->input('overview');
        $group = Group::where('name', $name)->first();

        if(!$group)
        {
            return Response()->json([
            'result' => 'Cannot find group: '.$name
            ], 500);
        }

        if (!empty($avatar)) {
            $group->avatar = $avatar;
        }
        if (!empty($banner)) {
            $group->cover_img = $banner;
        }
        if (!empty($overview)) {
            $group->description = $overview;
        }

        $group->save();

        return Response()->json([
            'result' => $group
            ]);
    }

    public function deleteGroup($group_id) {
        $group = Group::find($group_id);

        if ($group) {
            $group->delete();
        }

        return Response()->json([
            'group' => $group
            ]);
    }

    public function getGroups()
    {
        $groups = Group::all();
        return Response()->json([
            'groups' => $groups
            ]);
    }

    public function isAvailable(Request $request)
    {
        $id = $request->input('img_id');
        $image = ImageModel::where('id', $id)->first();
        if(!$image)
        {
            return Response()->json([
                'code' => -1
                ]);    
        }
        return Response()->json([
            'code' => 1
            ]);
    }

    public function groupFollow(Request $request)
    {
        $group_id = $request->input('group_id');
        $user_id = $request->input('user_id');
        $groupFollow = DB::table('group_follows')
        ->where('group_id', $group_id)
        ->where('follower_id', $user_id)
        ->get()->first();
        if($groupFollow)
            return Response()->json([
                'result' => false
                ], 200);
        $groupFollow = DB::table('group_follows')->insert([
            'group_id' => $group_id,
            'follower_id' => $user_id
            ]);
        return Response()->json([
            'result' => $groupFollow
            ], 200);
    }

    /**
     * get images inside particular group
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response 
     * @tags: array of tag
     * @max: maximum tag
     * @min: minimum tag
     */
    public function getGroup(Request $request)
    {
        // images per page in group page
        $load_per_page = 12;
        // accept parameters
        $group_name = $request->input('group');
        $page = $request->input('active_page');
        
        $group = Group::where('slug', $group_name)->first();

        // if group matching is not existing
        if(!$group)
        {
            return Response()->json([
                'result' => -1
            ], 404);
        }

        // log the visit
        $log = new Log([
                'ip_address' => $request->input('ip'),
                'path' => 'group/' . $group_name,
                'refer' => $group->name
                ]);
        if($request->input('ip')!="") $log->save();

        $group_name = $group->name;
        $images = Group_image::join('images','images.id','=','group_image.image_id')
                  ->join('users', 'users.id', '=', 'images.upload_by')  
                  ->where('group_image.group_id', $group_name);

        $image_length = $images
                        ->select(DB::raw('count(images.id) as length'))->first()->length;

        $images = $images
                 ->select('images.*', 'users.id as userid', 'users.*', 'images.id as id', 'images.created_at as created_at', 'images.likes as likes')
                 ->orderBy('images.updated_at','desc')
                 ->offset($page * $load_per_page)
                 ->limit($load_per_page)
                 ->get();
        
        $gallery = Gallery::leftJoin('gallery_groups', 'galleries.id', '=', 'gallery_groups.gallery_id')
                   ->leftJoin('groups','groups.id', '=', 'gallery_groups.group_id')
                   ->where('groups.name', $group_name)
                   ->where('groups.status', '=', 'active')
                   ->select('galleries.*')
                   ->get();            
        
        // refresh the thumbnail url from original to refined
        $all = collect();
        foreach ($images as $item)
        {
            // get view
            $image_view = Image_View::where('image_id','=',$item->id)
                          ->get();
            // get favorite
            $image_fav = FavImage::where('image_id', '=', $item->id)
                         ->get();
            // get comment
            $image_comment = Image_comment::where('image_id', '=', $item->id)
                             ->get();
            $filename = $item->s3_id;
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item->s3_id = $thumb_filename;
            $item->now = $this->now($item->updated_at);
            $item->view = count($image_view);
            $item->favorites = count($image_fav);
            $item->comments = count($image_comment);
            $all->push($item);
        }

        $follows = Group_Follows::join('groups','groups.id','=','group_follows.group_id')
                   ->where('groups.name', $group_name) 
                   ->select(DB::raw('count(group_follows.id) as length'))->first()->length;
        
        // get group creator
        $creator = Users::where('id', $group->owner_id)
                   ->first();

        // get recent log history
        $recent_visit = DB::table('log as tb1')
                ->where('tb1.id', DB::raw('(select id from log tb2 where tb1.ip_address = tb2.ip_address AND tb1.path = tb2.path order by tb2.created_at desc limit 1)'))
                ->where('tb1.ip_address', $request->input('ip'))
                ->orderBy('tb1.created_at', 'desc')
                ->limit(5)
                ->get();           

        return Response()->json([
            'gallery' => $gallery,
            'group' => $group,
            'length' => $image_length,
            'images' => $all,
            'follow' => $follows,
            'creator' => $creator,
            'recent' => $recent_visit
        ]);
    }

    /**
     * get images inside particular tag
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response 
     * @tags: array of tag
     * @max: maximum tag
     * @min: minimum tag
     */
    public function getTag(Request $request)
    {
        // images per page in group page
        $load_per_page = 12;
        // accept parameters
        $tag_name = $request->input('tag');
        $page = $request->input('active_page');
        
        $tag = Tag::where('slug', $tag_name)->first();

        // if group matching is not existing
        if(!$tag)
        {
            return Response()->json([
                'result' => -1
            ], 404);
        }

        $images = Image_tag::leftJoin('images','images.id','=','image_tags.image_id')
                  ->leftJoin('users', 'users.id', '=', 'images.upload_by')  
                  ->where('image_tags.tag_id', $tag->id);

        $image_length = $images
                        ->select(DB::raw('count(image_id) as length'))->first()->length;

        $images = $images
                 ->select('images.*', 'users.id as userid', 'users.*', 'images.likes as likes', 'images.id as id', 'images.created_at as created_at')
                 ->orderBy('images.created_at','desc')
                 ->offset($page * $load_per_page)
                 ->limit($load_per_page)
                 ->get();
        
        // refresh the thumbnail url from original to refined
        $all = collect();
        foreach ($images as $item)
        {
            // get view
            $image_view = Image_View::where('image_id','=',$item->id)
                          ->get();
            // get favorite
            $image_fav = FavImage::where('image_id', '=', $item->id)
                         ->get();
            // get comment
            $image_comment = Image_comment::where('image_id', '=', $item->id)
                             ->get();
            $filename = $item->s3_id;
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item->s3_id = $thumb_filename;
            $item->now = $this->now($item->created_at);
            $item->view = count($image_view);
            $item->favorites = count($image_fav);
            $item->comments = count($image_comment);
            $all->push($item);
        }

        $tags = DB::table('tags')
        ->join('image_tags', 'tags.id', '=', 'image_tags.tag_id')
        ->join('images', 'images.id', '=', 'image_tags.image_id')
        ->select('tags.name','tags.slug',DB::raw('sum(images.downloads) as sum'))->groupBy('tags.name')->orderBy('tags.created_at', 'desc')->get();

        $max = 0;
        $min = 0;
        foreach ($tags as $key) {
            $max = max($max, $key->sum);
            $min = min($min, $key->sum);
        }

        return Response()->json([
            'tag' => $tag,
            'length' => $image_length,
            'images' => $all,
            'tags' => $tags,
            'min' => $min,
            'max' => $max
        ]);
    }

    /**
     * get tags for tag cloud
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response 
     * @tags: array of tag
     * @max: maximum tag
     * @min: minimum tag
     */
    public function getTagcloud()
    {
        $tag_limit = env('TAG_CLOUD_LIMIT');
        $tags = DB::table('tags')
        ->join('image_tags', 'tags.id', '=', 'image_tags.tag_id')
        ->join('images', 'images.id', '=', 'image_tags.image_id')
        ->select('tags.name','tags.slug',DB::raw('sum(images.downloads) as sum'))
        ->groupBy('tags.name')
        ->orderBy('tags.created_at', 'desc')
        ->limit($tag_limit)
        ->get();

        $max = 0;
        $min = 0;
        foreach ($tags as $key) {
            $max = max($max, $key->sum);
            $min = min($min, $key->sum);
        }

        return Response()->json([
            "tags" => $tags,
            "max" => $max,
            "min" => $min
            ]);
    }

    public function searchWallpaper(Request $request)
    {
        // $image = ImageModel::where('id', '22')->first();
        // return $image->getWallpaperTags();

        $input = $request->input('searchkey');

        // $page = env('SEARCH_PER_PAGE');
        $query = app(\LaravelCloudSearch\CloudSearcher::class)->newQuery();

        $result = $query->searchableType(ImageModel::class)
            ->qOr(function($builder) use ($input) {
                foreach($input as $key)
                {
                    $builder = $builder->phrase($key);
                }
            })
            ->paginate(24);
        
        // refresh the thumbnail url from original to refined
        $all = collect();
        foreach ($result as $item)
        {
            $image = ImageModel::leftJoin('users', 'users.id', '=', 'images.upload_by')  
                    ->where('images.id', $item->target_id)
                    ->select('images.*', 'users.id as userid', 'users.*', 'images.likes as likes', 'images.id as id', 'images.created_at as created_at')
                    ->first();
            $item = $image;
            // get view
            $image_view = Image_View::where('image_id','=',$item->id)
                          ->get();
            // get favorite
            $image_fav = FavImage::where('image_id', '=', $item->id)
                         ->get();
            // get comment
            $image_comment = Image_comment::where('image_id', '=', $item->id)
                             ->get();
            $filename = $image->s3_id;
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item->s3_id = $thumb_filename;
            $item->now = $this->now($item->created_at);
            $item->view = count($image_view);
            $item->favorites = count($image_fav);
            $item->comments = count($image_comment);
            $all->push($item);
        }
        // $query = app(\LaravelCloudSearch\CloudSearcher::class)->newQuery();
        // $query->or(function($builder) {
        //     $builder->phrase('tulip')
        //         ->phrase('test');
        // });
        // $results = $query->searchableType(ImageModel::class)->get();
        // return $results;

        // get AWS cloudsearch result
        // $result = ImageModel::search($input)->get();
        return Response()->json([
            "result" => $all,
            "total" => $result->total()
            ]);        
    }
}
