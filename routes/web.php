<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Route::get('register/confirmation/{token}', 'UserController@confirmEmail');

// Route::get('verify-email/{token}', [
//     'as' => 'public.account.verify-email',
//     // 'uses' => 'Pub\AccountController@verifyEmail'
//     'uses' => 'UserController@confirmEmail'
// ])->where('token', '[a-zA-Z0-9]+');
Route::get('/home', 'HomeController@index')->name('home');

