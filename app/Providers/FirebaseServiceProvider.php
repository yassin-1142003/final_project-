<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Contract\Storage;

class FirebaseServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('firebase.storage', function ($app) {
            $factory = (new Factory)
                ->withServiceAccount(base_path('firebase-credentials.json'))
                ->withDefaultStorageBucket(config('firebase.credentials.storage_bucket'));

            return $factory->createStorage();
        });
    }

    public function boot()
    {
        //
    }
} 