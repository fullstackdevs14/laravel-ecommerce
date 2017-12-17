<?php

namespace App\Http\Controllers;

use App\User;
use App\ImageModel;
use App\User_Settings;
use App\Models\Group;
use App\Models\User\User_oAuth;
use App\Models\User\User_Email;
use App\Models\User\User_Follow;
use App\Models\Image\Group_Follows;
use App\Models\Image\Group_image;
use App\Models\Image\Post;
use App\Models\Image\Image_comment;
use App\Models\Image\Image_View;
use App\Models\Image\User_like;
use App\Models\User\Favorite\FavImage;
use App\Models\User\User_Ignore;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Event;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Storage;
use JWTAuth;
use Mail;
use App\Mail\ConfirmationEmail;
use App\Mail\ContactUs;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;

class UserController extends Controller {

    public function checkSession(Request $request)
    {
        // crypted Token
        $encrypt = $request->input('token');
        $decrypt = Crypt::decrypt($encrypt);
        // decrypt parameter
        $data = json_decode($decrypt);
        $user_id = $data->user_id;
        $time = $data->time;

        $user = User::where('id', $user_id)->first();
        if(!$user)
        {
            return Response()->json([
                'code' => -1
            ], 400);    
        }
        // create session expired after 15 min
        if(intval((strtotime(date('Y-m-d H:i:s'))-strtotime($time->date))/(60*60))>5)
        {
            return Response()->json([
                'code' => 0
            ], 400);
        }

        return Response()->json([
                'code' => 1
            ]);
    }

    public function signup(Request $request)
    {
        $this->validate($request, [
            'firstname' => 'required',
            'lastname' => 'required',
            'username' => 'required|unique:users',
            'password' => 'required'
            ]);

        // prevent duplicated email
        $user_email = User_Email::where('email', $request->input('email'))->get();
        if(count($user_email)>0) 
        {
            return response()->json([
                'email' => 'Email has been used!'
            ], 400);
        }

        $user = new User([
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'username' => $request->input('username'),
            'avatar' => env('AVATAR'),
            'password' => bcrypt($request->input('password'))
            ]);

        $user->save();

        // register to User Email for multi-email option
        $user_email = new User_Email([
            'email' => $request->input('email'),
            'user_id' => $user->id,
            'verification_token' => md5($request->input('email'))
            ]);
        $user_email->save();

        // Send Email Verificaion Message
        /**
         * call event Registered: RegisteredListener, Reference Providers/EventServiceProvider.php
         */
        event(new Registered($user_email));

        // Mail::to($user->email)->send(new ConfirmationEmail($user));
        return response()->json([
            'message' => 'Successfully created user!'
            ], 201);
    }

    public function signin(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
            'password' => 'required'
            ]);
        $credentials = $request->only('username', 'password');
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'error' => 'Invalid username and/or password!'
                    ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Could not create token!'
                ], 500);
        }
        $user = User::whereusername($request->input('username'))->first();
        $user_email = User_Email::where('user_id', $user->id)->first();
        if(!$user_email)
            return response()->json([
                'error' => 'Invalid username and/or password!'
                ], 500);
        // user is verified
        if($user_email->verified_at){
            // create security token
            $time = Carbon::now();
            $token = ([
                'user_id'=> $user->id,
                'time' => $time
                ]);
            $encrypted = Crypt::encrypt(json_encode($token));

            return response()->json([
                'user' => $user,
                'token' => $encrypted
                ], 200);
        }
        else
            return response()->json([
                'error' => 'Almost there! To activate your new account, find the email we sent to '. $user_email->email .' and click the link! To change your email, click here.'
                ], 401);
    }

    public function confirmEmail($token)
    {
        // Change verify value 
        $user_email = User_Email::where('verification_token',$token)->firstOrFail();
        User_Email::where('verification_token',$token)->firstOrFail()->hasVerified();
        event(new Event($user_email));
        if($user_email -> primary == 1)
        {
            $url = env('DOMAIN');    
        }
        else
        {
            $url = env('DOMAIN')."/settings/info";    
        }

        return redirect($url);
    }

    public function show($id)
    {
        $user = User::find($id);
        return response()->json([
            'user' => $user
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

    /*
    **
        get profile information - comment, about, activity list
    **
    */
    public function getProfile(Request $request)
    {
        $username = $request->input('username');
        $visitor = $request->input('user');
        $is_ban = 0;
        
        $user = User::where('username', $username)->first();
        // if username is not available
        if(!$user)
        {
            return Response()->json([
            'code' => -1
            ], 404);    // return 404 error
        }

        // if user logged in & get id
        if($visitor)
        {
            $user_ignore = User_Ignore::where('user_id', $user->id)->where('banned_id', $visitor['id'])->get();    
            if(count($user_ignore) != 0) $is_ban = 1;
            $user['isFollowed'] = User_Follow::where('user_id', $visitor['id'])
            ->where('follower_id', $user->id)
            ->count('user_id');
        }

        $follow['following'] = User::join('user_follows','user_follows.user_id','=','users.id')
        ->where('users.id',$user->id)
        ->count('user_follows.user_id');
        $follow['follower'] = User::join('user_follows','user_follows.follower_id','=','users.id')
        ->where('users.id',$user->id)
        ->count('user_follows.user_id');

        $all = $this->getActivityByID($user, 0);

        // get like and favorite
        $favorite = FavImage::where('user_id', $user->id)
                    ->count('id');
        $upload = ImageModel::where('upload_by', $user->id)
                    ->get()
                    ->count();                    
        $like = User_like::where('user_id', $user->id)
                ->count('id');

        return response()->json([
            'user' => $user,
            'report' => $all,
            'upload' => $upload,
            'follow' => $follow,
            'is_ban' => $is_ban,
            'favorite' => $favorite,
            'like' => $like
        ]);    
    }

    /*
    **
        get profile information - comment, about, activity list
    **
    */
    public function getActivity(Request $request)
    {
        $username = $request->input('username');
        $offset = $request->input('offset');

        $user = User::where('username', $username)->first();
        // if username is not available
        if(!$user)
        {
            return Response()->json([
            'code' => -1
            ], 404);    // return 404 error
        }

        $all = $this->getActivityByID($user, $offset);

        return response()->json([
            'report' => $all
        ]);    
    }

    public function getSlugFromArray($arr)
    {
        $buffer = explode(",", $arr);
        $new_array = collect();
        foreach ($buffer as $item) {
            $query = Group::where("name", $item)
                    ->select('slug')
                    ->first();
            $temp['name'] = $item;
            $temp['slug'] = $query['slug'];
            $new_array->push($temp);
        }
        return $new_array;
    }
    /*
    **
        get activity lists based on user ID
    **
    */
    public function getActivityByID($user, $offset)
    {

        $image = ImageModel::where('upload_by','=',$user->id)
        ->join('users','users.id','=','images.upload_by')
        ->join('group_image', 'images.id', '=', 'group_image.image_id','left outer')
        ->select('*', 'images.id','images.upload_date as created_at', 'users.avatar as avatar','users.username as author', DB::raw('group_concat(group_image.group_id) as list'), DB::raw("'1' as rtype"))
        ->groupby('images.id')
        ->get();
        $post = Post::where('author_id','=',$user->id)
        ->orWhere('user_id','=',$user->id)
        ->join('users as user1','user1.id','=','posts.author_id')
        ->join('users as user2','user2.id','=','posts.user_id')
        ->select('posts.created_at','user1.avatar as avatar', 'user1.username as author', 'posts.content', 'user2.username as target', DB::raw("'2' as rtype"))
        ->get();
        $image_comment = Image_comment::where('author_id','=', $user->id)
        ->join('users','users.id','=','image_comments.author_id')
        ->join('images','image_comments.image_id','=','images.id')
        ->select('images.id','images.s3_id', 'images.title' ,'users.username as author','users.avatar as avatar','image_comments.content','image_comments.created_at as created_at', DB::raw("'3' as rtype"))
        ->get();
        $favimage = FavImage::where('user_id','=',$user->id)
        ->join('users','users.id','=','user_fav_images.user_id')
        ->join('images','images.id','=','user_fav_images.image_id')
        ->select('user_fav_images.id as id', 'images.id as pid', 'images.s3_id', 'images.title', 'user_fav_images.created_at', 'users.avatar','users.username', DB::raw("'4' as rtype"))
        ->get();
        
        // put Sorts on Collection                    
        $all = collect();
        foreach ($image as $item)
        {
            $filename = $item['s3_id'];
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item['s3_id'] = $thumb_filename;
            $item['now'] = $this->now($item->created_at);
            $item['list'] = $this->getSlugFromArray($item->list);
            if($offset>0) $item['flag'] = 1;
            $all->push($item);
        }
        foreach ($post as $item){
            $filename = $item['s3_id'];
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item['s3_id'] = $thumb_filename;
            $item['now'] = $this->now($item->created_at);
            if($offset>0) $item['flag'] = 1;
            $all->push($item);
        }
        foreach ($image_comment as $item)
        {
            $filename = $item['s3_id'];
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item['s3_id'] = $thumb_filename;
            $item['now'] = $this->now($item->created_at);
            if($offset>0) $item['flag'] = 1;
            $all->push($item);
        }
        foreach ($favimage as $item){
            // get fav recent users(4)
            $f_user = ImageModel::where('images.id','=',$item['pid'])
                      ->join('user_fav_images', 'user_fav_images.image_id','=','images.id')
                      ->leftJoin('users','user_fav_images.user_id','=','users.id')
                      ->select('users.*')
                      ->orderBy('user_fav_images.created_at')
                      ->limit(4)
                      ->get();
            // get view
            $image_view = Image_View::where('image_id','=',$item['pid'])
                          ->get();
            // get like
            $image_like = User_like::where('image_id', '=', $item['pid'])
                          ->get();
            // get favorite
            $image_fav = FavImage::where('image_id', '=', $item['pid'])
                         ->get();
            // get comment
            $image_comment = Image_comment::where('image_id', '=', $item['pid'])
                             ->get();
            // whether profile user likes image
            $is_liked = User_like::where("user_id", $user->id)
                        ->where('image_id', $item['pid']);

            $filename = $item['s3_id'];
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item['s3_id'] = $thumb_filename;
            $item['now'] = $this->now($item->created_at);
            $item['recent_user'] = $f_user;
            $item['view'] = count($image_view);
            $item['likes'] = count($image_like);
            $item['fav'] = count($image_fav);
            $item['comment'] = count($image_comment);
            $item['is_liked'] = count($is_liked);

            if($offset>0) $item['flag'] = 1;
            $all->push($item);
        }

        //DESC based on created date
        $all = array_reverse(array_sort($all, function ($value) {
            return $value['created_at'];
        }));

        $all = array_slice($all, $offset, 10);
        return $all;
    }

    public function showByName($name)
    {
        $user = User::where('username', $name)->first();
        $follow['following'] = User::join('user_follows','user_follows.user_id','=','users.id')
        ->where('users.id',$user->id)
        ->count('user_follows.user_id');
        $follow['follower'] = User::join('user_follows','user_follows.follower_id','=','users.id')
        ->where('users.id',$user->id)
        ->count('user_follows.user_id');
        $image = ImageModel::where('upload_by','=',$user->id)
        ->join('users','users.id','=','images.upload_by')
        ->join('group_image', 'images.id', '=', 'group_image.image_id','left outer')
        ->select('*', 'images.id','images.upload_date as created_at', 'users.avatar as avatar','users.username as author', DB::raw('group_concat(group_image.group_id) as list'), DB::raw("'1' as rtype"))
        ->groupby('images.id')
        ->get();
        $post = Post::where('author_id','=',$user->id)
        ->orWhere('user_id','=',$user->id)
        ->join('users as user1','user1.id','=','posts.author_id')
        ->join('users as user2','user2.id','=','posts.user_id')
        ->select('posts.created_at','user1.avatar as avatar', 'user1.username as author', 'posts.content', 'user2.username as target', DB::raw("'2' as rtype"))
        ->get();
        $imageComment = Image_comment::where('author_id','=', $user->id)
        ->join('users','users.id','=','image_comments.author_id')
        ->join('images','image_comments.image_id','=','images.id')
        ->select('images.s3_id','users.username as author','users.avatar as avatar','image_comments.content','image_comments.created_at as created_at', DB::raw("'3' as rtype"))
        ->get();
        $favour = FavImage::where('user_id','=',$user->id)
        ->join('users','users.id','=','user_fav_images.user_id')
        ->join('images','images.id','=','user_fav_images.image_id')
        ->select('user_fav_images.id as id', 'images.s3_id', 'images.title', 'user_fav_images.created_at', 'users.avatar','users.username', DB::raw("'4' as rtype"))
        ->get();
        
        // put Sorts on Collection                    
        $all = collect();
        foreach ($image as $item)
        {
            $filename = $item['s3_id'];
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item['s3_id'] = $thumb_filename;
            $all->push($item);
        }
        foreach ($post as $item){
            $filename = $item['s3_id'];
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item['s3_id'] = $thumb_filename;
            $all->push($item);
        }
        foreach ($imageComment as $item)
        {
            $filename = $item['s3_id'];
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item['s3_id'] = $thumb_filename;
            $all->push($item);
        }
        foreach ($favour as $item){
            $filename = $item['s3_id'];
            $index = strpos($filename, ".");
            $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
            $item['s3_id'] = $thumb_filename;
            $all->push($item);
        }

        //DESC
        $all = array_reverse(array_sort($all, function ($value) {
            return $value['created_at'];
        }));

        // get Blacklist of user
        $user_list = User_Ignore::find($user->id);

        return response()->json([
            'user' => $user,
            'report' => $all,
            'follow' => $follow,
            'user'
            ]);
    }
    /**    
    * Social signup
    */
    public function oAuth(Request $request)
    {
        $email = $request->input('email');
        $accessToken = $request->input('accessToken');
        $providerId = $request->input('providerId');
        // $user = User_oAuth::where('user_oauth_tokens.token', $accessToken)->where('user_oauth_tokens.driver', $providerId)->get();
        // return $user;

        // check Social info
        $users = User::join('user_oauth_tokens', 'users.id', '=', 'user_oauth_tokens.user_id');

        $user = $users->where('user_oauth_tokens.token', $accessToken)
        ->where('user_oauth_tokens.driver', $providerId)->get();
        if($user && count($user)!=0)
            return Response()->json([
                'result' => $user->first()
                ], 200);


        $user_email = User_Email::where('email', $email)->first();
        if($user_email)
            $user_temp = User::where('id', $user_email->user_id)->first();
        else
            return Response()->json([
            'error' => 'No Result'
            ], 401);
        
        if($user_temp)
        {
            // create security token
            $time = Carbon::now();
            $token = ([
                'user_id'=> $user_temp->id,
                'time' => $time
                ]);
            $encrypted = Crypt::encrypt(json_encode($token));

            return Response()->json([
                'result' => $user_temp,
                'token' => $encrypted
                ], 200);
        }
        return Response()->json([
            'error' => 'No Result'
            ], 401);
    }

    public function oAuthSet(Request $request)
    {
        // return Response()->json([
        //     'driver' => $request->input('providerId'),
        //     'token' => $request->input('accessToken')
        // ]);
        $this->validate($request, [
            'username' => 'required|unique:users',
            'password' => 'required'
            ]);

        $user = new User([
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'username' => $request->input('username'),
            'avatar' => env('AVATAR'),
            'password' => bcrypt($request->input('password')),
            'token' => $token = md5(uniqid())
            ]);
        $user->save();

        // register to User Email for multi-email option
        $user_email = new User_Email([
            'email' => $request->input('email'),
            'user_id' => $user->id,
            'verification_token' => md5($request->input('email')),
            'verified_at' => 1
            ]);
        $user_email->save();

        $oAuth = new User_oAuth([
            'user_id' => $user->id,
            'driver' => $request->input('providerId'),
            'token' => $request->input('accessToken')
            ]);
        $oAuth->save();

        $user = User::where('id', $user->id)->first();
        // create security token
        $time = Carbon::now();
        $token = ([
            'user_id'=> $user->id,
            'time' => $time
            ]);
        $encrypted = Crypt::encrypt(json_encode($token));

        return Response()->json([
            "result" => $user,
            "token" => $encrypted
            ], 201);
    }

    public function group_image_upload(Request $request)
    {
        if($request->hasFile('photo')){
            $photo = $request->file('photo');

            /* filename generate */
            $filename = time() . "." . $photo->getClientOriginalExtension();
            $resource = '';
            if($request->input('type') == 0){
                $filename = 'avatars/'. $filename;
                $thumb_photo = Image::make($photo)->fit(150, 150);
                $resource = $thumb_photo->stream()->detach();
            }
            else{
                $filename = 'banner/'. $filename;
                $thumb_photo = Image::make($photo)->fit(1500, 250);
                $resource = $thumb_photo->stream()->detach();
            }

            // save to the S3 bucket
            $s3 = Storage::disk('thumbnails');

            /* make new thumbnail bucket url */
            $s3->put($filename, $resource, 'public');

            return Response()->json([
                "result" => $filename
            ], 200);
        }
    }

    public function avatar_upload(Request $request)
    {
        if($request->hasFile('photo')){
            $photo = $request->file('photo');

            /* filename generate */
            $filename = time() . "." . $photo->getClientOriginalExtension();
            $resource = '';
            if($request->input('type') == 0){
                $filename = 'avatars/'. $filename;
                $thumb_photo = Image::make($photo)->fit(150, 150);
                $resource = $thumb_photo->stream()->detach();
            }
            else{
                $filename = 'banner/'. $filename;
                $thumb_photo = Image::make($photo)->fit(1500, 250);
                $resource = $thumb_photo->stream()->detach();
            }

            // save to the S3 bucket
            $s3 = Storage::disk('thumbnails');

            /* make new thumbnail bucket url */
            $s3->put($filename, $resource, 'public');

            // save to the database (update user table)
            $user = User::find($request->input('uid'));
            if($request->input('type') == 0)
                $user->avatar = $filename;
            else
                $user->cover_img = $filename;
            $user->save();

            return Response()->json([
                "result" => $filename,
                "user" => $user,
                "code" => 1
            ], 200);
        }
    }

    public function avatar_change(Request $request)
    {
        $user = User::find($request->input('id'));
        $user->avatar = $request->input('avatar');
        $user->save();
        return Response()->json([
            "result" => $user
            ], 200);
    }

    public function banner_change(Request $request)
    {
        $user = User::find($request->input('id'));
        $user->cover_img = $request->input('banner');
        $user->save();
        return Response()->json([
            "result" => $user
            ], 200);
    }

    public function setInfo(Request $request)
    {
        $user = User::find($request->input('user_id'))->first();
        $user->firstname = $request->input('first');
        $user->lastname = $request->input('last');
        $user->save();
        return Response()->json([
            "result" => $user
            ], 200);
    }

    public function setSocial(Request $request)
    {
        $user = User::find($request->input('user_id'))->first();
        $user->facebook = $request->input('facebook');
        $user->twitter = $request->input('twitter');
        $user->google = $request->input('google');
        $user->save();
        return Response()->json([
            "result" => $user
            ], 200);
    }

    public function setAbout(Request $request)
    {
        $user = User::where('id', $request->input('about_user_id'))->first();
        $user->about = $request->input('about');
        $user->save();
        return Response()->json([
            "result" => $user
            ], 200);
    }

    public function setPost(Request $request)
    {
        // report Object create 
        $post = new Post([
            'user_id' => $request->input('user_id'),
            'author_id' => $request->input('author_id'),
            'content' => $request->input('post')
            ]);
        $post->save();

        $post = Post::where('user_id','=',$request->input('user_id'))
        ->join('users as user1','user1.id','=','posts.author_id')
        ->join('users as user2','user2.id','=','posts.user_id')
        ->select('posts.created_at','user1.avatar as avatar', 'user1.username as author', 'posts.content', 'user2.username as target', DB::raw("'2' as rtype"))
        ->orderBy('created_at', 'desc')
        ->first();
        
        $filename = $post->s3_id;
        $index = strpos($filename, ".");
        $thumb_filename = substr_replace($filename, "-bigthumbnail", $index, 0);
        $post->s3_id = $thumb_filename;
        $post['now'] = $this->now($post->created_at);
        $post['flag'] = 1;
        
        return Response()->json([
            "result" => $post
            ], 200);
    }

    public function setPassword(Request $request)
    {
        $user = User::find($request->input('user_id'));
        if(!$user)
        {
            return Response()->json([
                "result" => 'error'
                ], 200);
        }
        // check JWT auth for current password check
        $credentials = [];
        $credentials['username'] = $user->username;
        $credentials['password'] = $request->input('original');
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'error' => 'error'
                    ], 401);
            }
        } catch (JWTException $e) {
            return response()->json([
                'error' => 'error'
                ], 500);
        }

        // set new Password
        $user->password = bcrypt($request->input('new'));
        $user->save();

        return Response()->json([
            "result" => $user
            ], 200);
    }    

    public function blockUser(Request $request)
    {
        $user_id = $request->input('uid');
        $block_name = $request->input('block_name');

        $user = User::whereusername($block_name)->first();
        // if user not exist
        if(!$user)
        {
            return Response()->json([
                "result" => 'error'
                ], 400);
        }
        
        $user_ignore = new User_Ignore([
            'user_id' => $user_id,
            'banned_id' => $user->id,
            ]);

        // save to Database
        $user_ignore->save();
        return Response()->json([
                "result" => 1
                ], 200);
    }    

    public function unBlockUser(Request $request)
    {
        $user_id = $request->input('uid');
        $block_name = $request->input('block_name');

        $user = User::whereusername($block_name)->first();
        // if user not exist
        if(!$user)
        {
            return Response()->json([
                "result" => 'error'
                ], 400);
        }
        return $user;
        $user_ignore = User_Ignore::where('user_id', $user_id)
                       ->where('banned_id', $user->id)    
                       ->first();
        return $user_ignore;                       
        $user_ignore->remove();
        return Response()->json([
                "result" => 1
                ], 200);
    }    

    public function isBlock(Request $request)
    {
        $user_id = $request->input('uid');
        $block_name = $request->input('block_name');
        
        $user = User::whereusername($block_name)->first();
        $user_ignore = User_Ignore::where('user_id', $user->id)->where('banned_id', $user_id)->get();
        
        if(count($user_ignore) != 0) 
        {
            return Response()->json([
                "result" => 1
                ], 200);
        }
        return Response()->json([
            "result" => 0
            ], 200);

    }

    public function isBlockImage(Request $request)
    {
        $user_id = $request->input('uid');
        $image_name = $request->input('block_name');
        
        $img = ImageModel::where('id', $image_name)->first();
        if(!$img)
        {
            return Response()->json([
                'code' => -1
            ]);    
        }
        
        $user = $img->upload_by;
        $user_ignore = User_Ignore::where('user_id', $user)->where('banned_id', $user_id)->get();
        
        if(count($user_ignore) != 0) 
        {
            return Response()->json([
                "result" => 1
                ], 200);
        }
        return Response()->json([
            "result" => 0
            ], 200);

    }

    public function getEmailList(Request $request)
    {
        $user_id = $request->input('uid');

        // confirm Newly added multi-email
        $new = User_Email::where('user_id', $user_id)->where('verified_at', 2)->first();
        $flag = 0;
        if($new)
        {
            $new->verified_at = 1;
            $new->save();
            $flag = 1;
        }        

        $user_email = User_Email::where('user_id', $user_id)->select('email','primary','verified_at')->get();

        return Response()->json([
            "result" => $user_email,
            "flag" => $flag
            ], 200);

    }

    public function addMail(Request $request)
    {
        $user_id = $request->input('uid');
        $mail = $request->input('mail');

        $user_email = User_Email::where('email', $mail)->get();
        if(count($user_email)>0)
        {
            // if email is already used
            return Response()->json([
            "result" => 0
            ], 200);    
        }
        // else
        else
        {
            // register to User Email for multi-email option
            $user_email = new User_Email([
                'email' => $mail,
                'user_id' => $user_id,
                'verification_token' => md5($request->input('mail')),
                'primary' => 0
                ]);
            $user_email->save();

            // Send Email Verificaion Message
            /**
             * call event Registered: RegisteredListener, Reference Providers/EventServiceProvider.php
             */
            event(new Registered($user_email));
        }

        return Response()->json([
            "result" => 1
            ], 200);
    }

    public function resendMail(Request $request)
    {
        $user_id = $request->input('uid');
        $mail = $request->input('mail');

        $user_email = User_Email::where('email', $mail)->get();
        if(count($user_email)<0)
        {
            // if email is already used
            return Response()->json([
            "result" => 0
            ], 200);    
        }
        // else
        else
        {
            // Send Email Verificaion Message
            /**
             * call event Registered: RegisteredListener, Reference Providers/EventServiceProvider.php
             */
            $user_email = $user_email->first();
            $user_email->verification_token = md5(str_random(10));
            $user_email->save();
            event(new Registered($user_email));
        }

        return Response()->json([
            "result" => 1
            ], 200);
    }

    public function removeMail(Request $request)
    {
        $user_id = $request->input('uid');
        $mail = $request->input('mail');

        $user_email = User_Email::where('email', $mail)->where('user_id', $user_id)->get();
        if(count($user_email)<=0)
        {
            // if email is already used
            return Response()->json([
            "result" => 0
            ], 200);    
        }
        // else
        else
        {
            // register to User Email for multi-email option
            $user_email = User_Email::where('email', $mail)->where('user_id', $user_id)->first();
            $user_email->delete();
        }

        // return user email again
        $user_email = User_Email::where('user_id', $user_id)->select('email','primary','verified_at')->get();

        return Response()->json([
            "result" => $user_email
            ], 200);

    }

    public function setPrimary(Request $request)
    {
        $user_id = $request->input('uid');
        $mail = $request->input('mail');

        $user_email = User_Email::where('email', $mail)->where('user_id', $user_id)->get();
        if(count($user_email)<=0)
        {
            // if email is already used
            return Response()->json([
            "result" => 0
            ], 200);    
        }
        // else
        else
        {
            // set all primary to 0
            $user_email = User_Email::where('user_id', $user_id)->where('primary', 1)->first();
            $user_email->primary = 0;
            $user_email->save();

            // register to User Email for multi-email option
            $user_email = User_Email::where('email', $mail)->where('user_id', $user_id)->first();
            $user_email->primary = 1;
            $user_email->save();
        }

        // return user email again
        $user_email = User_Email::where('user_id', $user_id)->select('email','primary','verified_at')->get();

        return Response()->json([
            "result" => $user_email
            ], 200);
    }

    public function getConnections(Request $request) {
        $user_id = $request->input('user_id');
        $filter = strtolower($request->input('filter'));

        $followers = User_Follow::join('users', 'users.id', '=', 'user_follows.user_id')
        ->where('follower_id', $user_id)
        ->whereRaw("(lower(concat(`users`.`firstname`, ' ', `users`.`lastname`)) like '%".$filter."%' or lower(`users`.`username`) like '%".$filter."%')")
        ->select('users.id', 'users.avatar', 'users.firstname', 'users.lastname', 'users.username')
        ->limit(5)->get();
        $following = User_Follow::join('users', 'users.id', '=', 'user_follows.follower_id')
        ->where('user_id', $user_id)
        ->whereRaw("(lower(concat(`users`.`firstname`, ' ', `users`.`lastname`)) like '%".$filter."%' or lower(`users`.`username`) like '%".$filter."%')")
        ->select('users.id', 'users.avatar', 'users.firstname', 'users.lastname', 'users.username')
        ->limit(5)->get();

        if (is_array($followers) && is_array($following))
            $connections = array_merge($followers, $following);
        else if (is_array($followers))
            $connections = $followers;
        else
            $connections = $following;

        return Response()->json([
            "result" => $connections,
            ], 200);
    }

    public function contactUs(Request $request) {
        $name = $request->input('name');
        $email = $request->input('email');
        $text = $request->input('text');
        // create invite template
        $validateEmail = new ContactUs();
        // message body
        $content = $name;
        $content.= " requests some query about <a href='https://www.desktopnexus.com'>DesktopNexus.com</a>";
        // send email
        $validateEmail->to($email, $name)
        ->setViewData([
            'FNAME' => $name,
            'CONTENT' => $text
            ]);
        Mail::queue($validateEmail);
    }

    public function searchUser(Request $request)
    {

        $input = $request->input('searchkey');
        $user_id = $request->input('userid');
        // $page = env('SEARCH_PER_PAGE');
        $query = app(\LaravelCloudSearch\CloudSearcher::class)->newQuery();

        $result = $query->searchableType(User::class)
            ->qOr(function($builder) use ($input) {
                foreach($input as $key)
                {
                    $builder = $builder->phrase($key);
                }
            })
            ->paginate(12);

         // refresh the thumbnail url from original to refined
        $all = collect();
        foreach ($result as $item)
        {
            $item = User::getSearchItem($item->target_id, $user_id);
            $all->push($item);
        }       
        // refresh the thumbnail url from original to refined
        return Response()->json([
            "result" => $all,
            "total" => $result->total()
            ]);        
    }

    public function searchGroup(Request $request)
    {

        $input = $request->input('searchkey');
        $uid = $request->input('userid');

        // $page = env('SEARCH_PER_PAGE');
        $query = app(\LaravelCloudSearch\CloudSearcher::class)->newQuery();

        $result = $query->searchableType(Group::class)
            ->qOr(function($builder) use ($input) {
                foreach($input as $key)
                {
                    $builder = $builder->phrase($key);
                }
            })
            ->paginate(12);

         // refresh the thumbnail url from original to refined
        $all = collect();
        foreach ($result as $item)
        {
            $item = Group::getSearchItem($item->target_id, $uid);
            $all->push($item);
        }       
        // refresh the thumbnail url from original to refined
        return Response()->json([
            "result" => $all,
            "total" => $result->total()
            ]);        
    }

    public function setExperience(Request $request) {
        $user_id = $request->input('user_id');
        $exp = $request->input('exp');

        $user = User::find($user_id);
        if ($user) {
            $user->exp = $exp;
            $user->save();
            return Response()->json([
                "result" => 'ok',
                ], 200);
        }

        return Response()->json([
                "result" => 'user not found',
                ], 404);
    }

    public function setLevel(Request $request) {
        $user_id = $request->input('user_id');
        $user = User::find($user_id);

        if ($user) {
            $user->level += 1;
            $user->save();

            return Response()->json([
                "result" => 'ok',
                ], 200);
        }

        return Response()->json([
                "result" => 'user not found',
                ], 404);
    }

    public function followSearchGroup(Request $request)
    {
        $group = Group::where('name', $request->input('group'))
                    ->first();
        $user_id = $request->input('user_id');
        $group_id = 0;

        if($group)
            $group_id = $group->id;

        $group_follow = Group_Follows::where('group_id', $group_id)
                ->where('follower_id', $user_id)
                ->first();

        // if already followed by user                
        if($group_follow)
        {
            $group_follow->delete();
        }
        // if not followed, following
        else
        {
            $group_follow = DB::table('group_follows')->insert([
                'group_id' => $group_id,
                'follower_id' => $user_id
            ]);

        }

        $index = $request->input('index');
         $input = $request->input('searchkey');

        // $page = env('SEARCH_PER_PAGE');
        $query = app(\LaravelCloudSearch\CloudSearcher::class)->newQuery();

        $result = $query->searchableType(Group::class)
            ->qOr(function($builder) use ($input) {
                foreach($input as $key)
                {
                    $builder = $builder->phrase($key);
                }
            })
            ->paginate(12);

         // refresh the thumbnail url from original to refined
        
        $item = $result[$index];
        $all = Group::getSearchItem($item->target_id, $user_id);
        // refresh the thumbnail url from original to refined
        return Response()->json([
            "result" => $all
            ]);        
    }

    public function unfollowSearchGroup(Request $request)
    {
        $group = Group::where('name', $request->input('group'))
                    ->first();
        $user_id = $request->input('user_id');
        $group_id = 0;

        if($group)
            $group_id = $group->id;

        $group_follow = Group_Follows::where('group_id', $group_id)
                ->where('follower_id', $user_id)
                ->first();

        $group_follow->delete();      

        $index = $request->input('index');
         $input = $request->input('searchkey');

        // $page = env('SEARCH_PER_PAGE');
        $query = app(\LaravelCloudSearch\CloudSearcher::class)->newQuery();

        $result = $query->searchableType(Group::class)
            ->qOr(function($builder) use ($input) {
                foreach($input as $key)
                {
                    $builder = $builder->phrase($key);
                }
            })
            ->paginate(12);

         // refresh the thumbnail url from original to refined
        
        $item = $result[$index];
        $all = Group::getSearchItem($item->target_id, $user_id);
        // refresh the thumbnail url from original to refined
        return Response()->json([
            "result" => $all
            ]);        
    }

    public function followSearchUser(Request $request)
    {
        $target_id = $request->input('target_user');
        $user_id = $request->input('user_id');

        $user_follow = User_Follow::where('user_id', $user_id)
                ->where('follower_id', $target_id)
                ->first();
        
        // if already followed by user                
        if($user_follow)
        {
            $user_follow->delete();
        }
        // if not followed, following
        else
        {
            $user_follow = DB::table('user_follows')->insert([
                'user_id' => $user_id,
                'follower_id' => $target_id
            ]);

        }

        $index = $request->input('index');
         $input = $request->input('searchkey');

        // $page = env('SEARCH_PER_PAGE');
        $query = app(\LaravelCloudSearch\CloudSearcher::class)->newQuery();

        $result = $query->searchableType(User::class)
            ->qOr(function($builder) use ($input) {
                foreach($input as $key)
                {
                    $builder = $builder->phrase($key);
                }
            })
            ->paginate(12);

         // refresh the thumbnail url from original to refined
        
        $item = $result[$index];
        $all = User::getSearchItem($item->target_id, $user_id);
        // refresh the thumbnail url from original to refined
        return Response()->json([
            "result" => $all
            ]);        
    }

    public function unfollowSearchUser(Request $request)
    {
        $target_id = $request->input('target_user');
        $user_id = $request->input('user_id');

        $user_follow = User_Follow::where('user_id', $user_id)
                ->where('follower_id', $target_id)
                ->first();

        $user_follow->delete();
 
        $index = $request->input('index');
         $input = $request->input('searchkey');

        // $page = env('SEARCH_PER_PAGE');
        $query = app(\LaravelCloudSearch\CloudSearcher::class)->newQuery();

        $result = $query->searchableType(User::class)
            ->qOr(function($builder) use ($input) {
                foreach($input as $key)
                {
                    $builder = $builder->phrase($key);
                }
            })
            ->paginate(12);

         // refresh the thumbnail url from original to refined
        
        $item = $result[$index];
        $all = User::getSearchItem($item->target_id, $user_id);
        // refresh the thumbnail url from original to refined
        return Response()->json([
            "result" => $all
            ]);        
    }

    public function getHint(Request $request)
    {
        $hint = $request->input('hint');
        if(!$hint) 
        {
            // return all results based on json
            return Response()->json([
                "result" => []
                ]);
        }
        $query = User::where('username', 'LIKE', '%'.$hint.'%')
                // ->orWhere('lastname', 'LIKE', '%'.$hint.'%')
                // ->orWhere('username', 'LIKE', '%'.$hint.'%')
                ->select('username')
                ->get();
        // return all results based on json
        return Response()->json([
            "result" => $query
            ]);
    }

    public function setNotificationSettings(Request $request)
    {
        $user_id = $request->input('user_id');

        $setting = User_Settings::where('user_id', $user_id)->first();
        if ($setting === null) {
            $setting = new User_Settings();
            $setting->user_id = $user_id;
        }

        if ($request->has('newsletter')) {
            $setting->newsletter = $request->input('newsletter');
        }
        if ($request->has('announcement')) {
            $setting->announcement = $request->input('announcement');
        }
        if ($request->has('private_msg')) {
            $setting->private_msg = $request->input('private_msg');
        }
        if ($request->has('comment_wallpaper')) {
            $setting->comment_wallpaper = $request->input('comment_wallpaper');
        }
        if ($request->has('post_response')) {
            $setting->post_response = $request->input('post_response');
        }
        if ($request->has('comment_profile')) {
            $setting->comment_profile = $request->input('comment_profile');
        }
        if ($request->has('favorite')) {
            $setting->favorite = $request->input('favorite');
        }
        $setting->save();

        return Response()->json($setting);
    }

    public function getNotificationSettings($user_id)
    {
        
        return Response()->json($this->getUserSetting($user_id));
    }

    public function sendNotifications(Request $request)
    {
    	$user_id = $request->input('user_id');
        $setting = $this->getUserSetting($user_id);
        $type = $request->input('type');

        if ($type === 10 && $setting->private_msg) { // Send private message

        } else if ($type === 7 && $setting->comment_profile) { // Comment on profile

        } else if ($type === 2 && $setting->comment_wallpaper) { // Comment on wallpaper

        } else if ($type === 1 && $setting->favorite) { // Follow profile
            
        }
        return Response()->json($request->all());
    }

    private function getUserSetting($user_id)
    {
        $setting = User_Settings::where('user_id', $user_id)->first();
        if ($setting === null) {
            $setting = new User_Settings();
            $setting->user_id = $user_id;
        }
        return $setting;
    }
}