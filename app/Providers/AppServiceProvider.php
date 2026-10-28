<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        \Illuminate\Support\Facades\Gate::define('storage-usage.view', fn ($user) => $user->hasRole('Admin'));

        // TLS may terminate at the proxy while PHP receives an HTTP request.
        // Keep generated links HTTPS when the configured public URL uses HTTPS.
        if (parse_url((string) config('app.url'), PHP_URL_SCHEME) === 'https') {
            URL::forceScheme('https');
        }
    }
}
