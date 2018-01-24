<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/**
*	Auth
*/
Route::post('/user', [
	'uses' => 'UserController@signup'
]);

Route::post('/user/signin', [
	'uses' => 'UserController@signin'
]);

Route::post('/user/checksession', [
	'uses' => 'UserController@checkSession'
]);

Route::post('/user/avatar/upload', [
	'uses' => 'UserController@avatar_upload'
]);

Route::post('/group/uploadimage', [
	'uses' => 'UserController@group_image_upload'
]);

Route::post('/user/avatar/change', [
	'uses' => 'UserController@avatar_change'
]);

Route::post('/user/banner/change', [
	'uses' => 'UserController@banner_change'
]);

Route::post('/forgotpassword', [
	'uses' => 'ForgotController@sendForgotPasswordMail'
]);

Route::post('/forgotusername', [
	'uses' => 'ForgotController@sendForgotUsernameMail'
]);

Route::post('/changepassword', [
	'uses' => 'ForgotController@changePassword'
]);

Route::get('confirm/{token}', [
	'uses' => 'UserController@confirmEmail'
]);

Route::get('image/gallery', [
	'uses' => 'ImageController@getGallery'
]);

Route::post('image/followgallery', [
	'uses' => 'ImageController@followGallery'
]);

Route::get('image/gallerylist', [
	'uses' => 'ImageController@getGalleryList'
]);

Route::post('image/followgroup', [
	'uses' => 'ImageController@followGroup'
]);

Route::post('image/groupfollow', [
	'uses' => 'ImageController@groupFollow'
]);

Route::post('image/creategroup', [
	'uses' => 'ImageController@createGroup'
]);

Route::post('image/updategroup', [
	'uses' => 'ImageController@updateGroup'
]);

Route::delete('image/deletegroup/{group_id}', [
	'uses' => 'ImageController@deleteGroup'
]);

Route::get('image/groups', [
	'uses' => 'ImageController@getGroups'
]);

Route::get('image/search/{tag}', [
	'uses' => 'ImageController@onSearchTag'
]);

Route::post('/image/searchwallpaper', [
	'uses' => 'ImageController@searchWallpaper'
]);

Route::post('/user/searchuser', [
	'uses' => 'UserController@searchUser'
]);

Route::post('/group/searchgroup', [
	'uses' => 'UserController@searchGroup'
]);

// Social signup
Route::post('/oAuth', [
	'uses' => 'UserController@oAuth'
]);

Route::post('/oAuth/set', [
	'uses' => 'UserController@oAuthSet'
]);
/**
* End Auth
*/
Route::post('/image/uploadimageurl',[
	'uses' => 'ImageController@uploadImageUrl'
]);

Route::post('/upload',[
	'uses' => 'ImageController@store'
]);

Route::post('/image/getwallpaperinfo', [			
	'uses' => 'ImageController@show'
]);

Route::post('/image/getwallpaper', [
	'uses' => 'ImageController@getWallpaper'
]);

Route::post('/image/category/{name}', [
	'uses' => 'ImageController@getCategory'
]);

// Image Edit
Route::post('/image/edit', [
	'uses' => 'ImageController@edit'
]);

Route::post('/image/getimage', [
	'uses' => 'ImageController@getImage'
]);

Route::post('/image/like', [
	'uses' => 'ImageController@like'
]);
// Image comment section
Route::post('/image/cmt', [
	'uses' => 'ImageController@newComment'
]);

Route::get('/image/cmt/{id}', [
	'uses' => 'ImageController@getComment'
]);
// End Comment

Route::delete('/image/{id}', [
	'uses' => 'ImageController@destroy'
]);

Route::get('/user/{id}', [
	'uses' => 'UserController@show'
]);

Route::get('/user/profile/{name}', [
	'uses' => 'UserController@showByName'
]);

Route::post('/user/getprofile', [
	'uses' => 'UserController@getProfile'
]);

Route::post('/getgeneralinfo', [
	'uses' => 'ImageController@getGeneralInfo'
]);

Route::post('/getheaderinfo', [
	'uses' => 'ImageController@getHeader'
]);

Route::post('/user/getactivity', [
	'uses' => 'UserController@getActivity'
]);

Route::post('/invite', [
	'uses' => 'ImageController@invite'
]);

Route::post('/follow', [
	'uses' => 'ImageController@follow'
]);

Route::post('/report', [
	'uses' => 'ImageController@report'
]);

Route::post('/getwallpaperreportlist', [
	'uses' => 'ImageController@getWallpaperReportList'
]);

Route::post('/getuserreportlist', [
	'uses' => 'UserController@getUserReportList'
]);

Route::post('/savefavtree', [
	'uses' => 'ImageController@saveFavtree'
]);

// favorite
Route::post('/fav/{img_id}', [
	'uses' => 'ImageController@favorite'
]);

Route::get('/fav/{img_id}/{user_id}', [
	'uses' => 'ImageController@getFavInfo'
]);

Route::delete('fav/{img_id}/{user_id}', [
	'uses' => 'ImageController@delFavImage'
]);

Route::post('/user/setinfo', [
	'uses' => 'UserController@setInfo'
]);

Route::post('/user/setsocial', [
	'uses' => 'UserController@setSocial'
]);

Route::post('/user/setpass', [
	'uses' => 'UserController@setPassword'
]);

Route::post('/user/setabout', [
	'uses' => 'UserController@setAbout'
]);

Route::post('/user/setpost', [
	'uses' => 'UserController@setPost'
]);

Route::get('/user/follow/{user_id}', [
	'uses' => 'UserController@getFollowList'
]);

Route::post('/user/follow', [
	'uses' => 'UserController@follow'
]);

Route::post('/user/unfollow', [
	'uses' => 'UserController@unfollow'
]);

Route::post('/image/senddiamond', [
	'uses' => 'ImageController@sendDiamond'
]);

Route::post('/image/download', [
	'uses' => 'ImageController@downloadWallpaper'
]);

Route::post('/image/downloadtoken', [
	'uses' => 'ImageController@downloadToken'
]);

Route::get('/image/downloadimage', [
	'uses' => 'ImageController@downloadImage'
]);

Route::post('/image/socialupdate', [
	'uses' => 'ImageController@socialUpdate'
]);

Route::post('/image/isavail', [
	'uses' => 'ImageController@isAvailable'
]);

Route::post('/image/isfollowgallery', [
	'uses' => 'ImageController@isFollowGallery'
]);

Route::post('/image/isfollowgroup', [
	'uses' => 'ImageController@isFollowGroup'
]);

Route::post('/image/getgroup', [
	'uses' => 'ImageController@getGroup'
]);

Route::post('/image/gettag', [
	'uses' => 'ImageController@getTag'
]);

Route::post('/image/resize', [
	'uses' => 'ImageController@resize'
]);

Route::post('/user/block', [
	'uses' => 'UserController@blockUser'
]);

Route::post('/user/report', [
	'uses' => 'UserController@reportUser'
]);

Route::post('/user/unblock', [
	'uses' => 'UserController@unBlockUser'
]);

Route::post('/user/isblocked', [
	'uses' => 'UserController@isBlock'
]);

Route::post('/user/isblockedbyimageid', [
	'uses' => 'UserController@isBlockImage'
]);

Route::post('/user/getmaillist', [
	'uses' => 'UserController@getEmailList'
]);

Route::post('/user/addmail', [
	'uses' => 'UserController@addMail'
]);

Route::post('/user/resendmail', [
	'uses' => 'UserController@resendMail'
]);

Route::post('/user/removemail', [
	'uses' => 'UserController@removeMail'
]);

Route::post('/user/setprimary', [
	'uses' => 'UserController@setPrimary'
]);

Route::post('/image/gettagcloud', [
	'uses' => 'ImageController@getTagCloud'
]);

Route::post('/user/connections', [
	'uses' => 'UserController@getConnections'
]);

Route::post('contactus', [
	'uses' => 'UserController@contactUs'
]);

Route::post('/user/experience', [
	'uses' => 'UserController@setExperience'
]);

Route::post('/user/level', [
	'uses' => 'UserController@setLevel'
]);

Route::post('followsearchgroup', [
	'uses' => 'UserController@followSearchGroup'
]);

Route::post('unfollowsearchgroup', [
	'uses' => 'UserController@unfollowSearchGroup'
]);

Route::post('followsearchuser', [
	'uses' => 'UserController@followSearchUser'
]);

Route::post('unfollowsearchuser', [
	'uses' => 'UserController@unfollowSearchUser'
]);

Route::post('gethint', [
	'uses' => 'UserController@getHint'
]);

Route::post('getblocklist', [
	'uses' => 'UserController@getBlockList'
]);

Route::post('getbanlist', [
	'uses' => 'UserController@getBanList'
]);

Route::get('/user/settings/notification/{user_id}', [
	'uses' => 'UserController@getNotificationSettings'
]);

Route::post('/user/settings/notification', [
	'uses' => 'UserController@setNotificationSettings'
]);

Route::post('/notifications', [
	'uses' => 'UserController@sendNotifications'
]);

Route::post('/removewallpapergroup', [
	'uses' => 'ImageController@removeWallpaperGroup'
]);

Route::post('/rejectreport', [
	'uses' => 'ImageController@rejectReport'
]);

Route::post('/removetag', [
	'uses' => 'ImageController@removeTag'
]);

Route::post('/removewallpaper_report', [
	'uses' => 'ImageController@removeWallpaper_Report'
]);

Route::post('/getreportactivity', [
	'uses' => 'ImageController@getReportActivity'
]);

Route::prefix('payment')->middleware(['jwt.auth'])->group(function() {
	Route::get('methods', 'PaymentController@getMethods');
	Route::post('methods', 'PaymentController@addMethod');
	Route::post('methods/delete', 'PaymentController@deleteMethod');
	Route::post('purchase', 'PaymentController@buyDiamonds');
	Route::get('transactions', 'PaymentController@getTransactions');
	// Route::get('customers/all', 'PaymentController@allCustomers');
	// Route::post('customers/delete', 'PaymentController@deleteCustomer');
});
