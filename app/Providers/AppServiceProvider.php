<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Force HTTPS URLs when behind reverse proxy
        if ($this->app->environment('production') || request()->header('X-Forwarded-Proto') === 'https') {
            \URL::forceScheme('https');
        }
    }
}
