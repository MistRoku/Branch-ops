<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        // Named "api" limiter used by throttle:api middleware. Missing this
        // definition made every throttled API route throw a 500
        // (MissingRateLimiterException) instead of rate limiting.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Apply the business profile locale and timezone organization-wide.
        try {
            $profile = Cache::remember('business-profile-locale', 3600, fn () => \App\Models\BusinessProfile::current());
            config(['app.timezone' => $profile->timezone ?: config('app.timezone')]);
            config(['app.locale' => $profile->language ?: config('app.locale')]);
            date_default_timezone_set(config('app.timezone'));
            app('translator')->setLocale(config('app.locale'));
        } catch (\Throwable) {
            // Database may not be migrated yet (install, console).
        }
    }
}
