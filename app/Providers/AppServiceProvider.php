<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Storage;
use App\Image\Thumbnail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Models\Tag;
use Tymon\JWTAuth\Http\Parser\Cookies;
use Tymon\JWTAuth\Http\Parser\Parser;
use Tymon\JWTAuth\Http\Parser\AuthHeaders;
use Tymon\JWTAuth\Http\Parser\QueryString;
use Tymon\JWTAuth\Http\Parser\InputSource;
use Tymon\JWTAuth\Http\Parser\RouteParams;
use App\Html\GridBuilder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->app['view']->addNamespace('dn', base_path() . '/resources/views/dn');
        $this->app['view']->addNamespace('clickagy', base_path() . '/resources/views/clickagy');

        Schema::defaultStringLength(191);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('App\Image\Resource', function($app) {
            
            $uploadStorage = Storage::disk('uploads');
            $thumbnailStorage = Storage::disk('thumbnails');
            
            return new \App\Image\Resource($uploadStorage, $thumbnailStorage);
        });
        
        $this->app->singleton('App\Image\Thumbnail', function($app) {
            
            $thumbnailStorage = Storage::disk('thumbnails');
            
            if($app->environment() != 'production') {
                $cache = Cache::store('file');
            } else {
                $cache = Cache::store('memcached');
            }
            
            return new Thumbnail($thumbnailStorage, $cache);
            
        });
        
        $this->app->bind('App\Mail\ClickagyTransport', function($app) {
             
            $defaultOptions = [
                'allow_redirects' => false,
                'connect_timeout' => 3600,
                'headers' => [
                    'Api-Key' => \Config::get('services.clickagy.key'),
                ],
                'timeout' => 5,
                'base_uri' => 'https://api.clickagy.com'
            ];
            
            $options = array_merge_recursive($defaultOptions, \Config::get('services.clickagy.http', []));
            
            return new Client($options);
            
        });
        
        $this->app->singleton('tymon.jwt.parser', function ($app) {
            $parser = new Parser(
                $app['request'],
                [new Cookies(), new AuthHeaders(), new QueryString(), new InputSource(), new RouteParams()]
            );
        
            $app->refresh('request', $parser, 'setRequest');
        
            return $parser;
        });
        
        $this->app->bind('GridImage', function($app) {
            $builder = new GridBuilder($app['html'], $app['url'], $app['view']);
            return $builder;
        });
        
        $this->app->alias('GridImage', 'App\Html\GridBuilder');
        
    }
}
