<?php

namespace App\Providers;

use App\Support\SessionCookieName;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use PragmaRX\Google2FA\Google2FA;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Google2FA::class, fn () => new Google2FA);
    }

    public function boot(): void
    {
        config([
            'session.cookie' => SessionCookieName::prefixed((string) config('session.cookie')),
        ]);

        RateLimiter::for('redeem', function (Request $request) {
            return Limit::perMinute(config('otshare.rate_limit_redeem', 15))->by($request->ip());
        });
        RateLimiter::for('shares_create', function (Request $request) {
            return Limit::perMinute(config('otshare.rate_limit_shares_create', 30))->by($request->ip());
        });
        RateLimiter::for('upload', function (Request $request) {
            return Limit::perMinute(config('otshare.rate_limit_upload', 20))->by($request->ip());
        });
        RateLimiter::for('download', function (Request $request) {
            return Limit::perMinute(config('otshare.rate_limit_download', 30))->by($request->ip());
        });
        RateLimiter::for('confirm', function (Request $request) {
            return Limit::perMinute(config('otshare.rate_limit_confirm', 20))->by($request->ip());
        });
    }
}
