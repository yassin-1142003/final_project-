<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Force HTTPS in production
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Set default pagination
        $this->app['config']->set('pagination.per_page', config('api.pagination.per_page'));

        // Set API rate limiting
        $this->app['config']->set('api.throttle', [
            'max_attempts' => config('api.throttle.max_attempts'),
            'decay_minutes' => config('api.throttle.decay_minutes'),
        ]);
    }
} 