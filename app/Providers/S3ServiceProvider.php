<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Aws\S3\S3Client;

class S3ServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->bind(S3Client::class, function() {
            
            return new S3Client([
                'credentials' => [
                    // 'key' => config('services.aws.access_key_id'),
                    // 'secret' => config('services.aws.secret_access_key'),
                    'key' => config('AKIAJQUQU7NSU2GSX2GQ'),
                    'secret' => config('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'),
                ],
                // 'region' => config('services.aws.s3.region'),
                'region' => config('us-east-1'),
                'version' => 'latest'
            ]);
            
        });
    }
    
    public function register()
    {
        
    }
}