<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This value is used by the framework to redirect users after login.
     */
    public const HOME = '/home';

    /**
     * Register any route bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // Load API routes with "api" middleware and prefix
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Load web routes
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();
            return Limit::perMinute(60)->by($key);
        });
    }
}
