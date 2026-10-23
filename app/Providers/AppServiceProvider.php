<?php

namespace App\Providers;

use App\Models\BlogSetting;
use App\Models\MenuItem;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewInstance;

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
        View::composer('layouts.app', function (ViewInstance $view): void {
            $view->with('blogSettings', BlogSetting::query()->first());
        });

        View::composer('welcome', function (ViewInstance $view): void {
            $view->with('blogSettings', BlogSetting::query()->first());
        });

        View::composer('components.site-navigation', function (ViewInstance $view): void {
            $view->with('headerMenuItems', MenuItem::query()
                ->where('location', 'header')
                ->where('is_visible', true)
                ->orderBy('position')
                ->orderBy('id')
                ->get());
        });

        View::composer('components.site-footer', function (ViewInstance $view): void {
            $view->with('footerMenuItems', MenuItem::query()
                ->where('location', 'footer')
                ->where('is_visible', true)
                ->orderBy('position')
                ->orderBy('id')
                ->get());
        });

        RateLimiter::for('comment-submissions', function (Request $request): Limit {
            return Limit::perMinute(5)
                ->by(($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip());
        });

        RateLimiter::for('content-image-uploads', function (Request $request): Limit {
            return Limit::perMinute(10)
                ->by($request->user()->getAuthIdentifier().'|'.$request->ip());
        });
    }
}
