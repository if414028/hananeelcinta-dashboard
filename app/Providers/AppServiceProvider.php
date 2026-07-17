<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Services\WebsiteSettings;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
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
        View::composer('components.layouts.public', fn ($view) => $view->with('siteSettings', app(WebsiteSettings::class)->public()));

        foreach ([Announcement::class, FamilyAltar::class, PastorMessage::class] as $model) {
            $model::saved(fn () => $this->forgetPublicHomeCache());
            $model::deleted(fn () => $this->forgetPublicHomeCache());
            $model::restored(fn () => $this->forgetPublicHomeCache());
        }

        Gate::before(fn ($user, string $ability): ?bool => $user->hasRole('Super Admin') ? true : null);

        RateLimiter::for('admin-login', fn (Request $request): Limit => Limit::perMinute(5)
            ->by(mb_strtolower((string) $request->input('email')).'|'.$request->ip()));

        RateLimiter::for('public-api', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->ip()));

        RateLimiter::for('prayer-request', fn (Request $request): Limit => Limit::perMinutes(10, 5)
            ->by($request->ip()));
    }

    private function forgetPublicHomeCache(): void
    {
        Cache::forget('public.home');
        Cache::forget('public.home.v2');
    }
}
