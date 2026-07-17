<?php

namespace App\Providers;

use App\Auth\Contracts\FirebaseTokenVerifier;
use App\Auth\GoogleFirebaseTokenVerifier;
use App\Models\Announcement;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Services\WebsiteSettings;
use Illuminate\Auth\Notifications\ResetPassword;
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
        $this->app->bind(FirebaseTokenVerifier::class, GoogleFirebaseTokenVerifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(fn ($user, string $token): string => route('admin.password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()]));

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

        RateLimiter::for('mobile-auth', fn (Request $request): Limit => Limit::perMinute(30)
            ->by($request->ip()));
    }

    private function forgetPublicHomeCache(): void
    {
        Cache::forget('public.home');
        Cache::forget('public.home.v2');
        Cache::forget('api.home.v1');
        Cache::forget('public.sitemap.v1');
    }
}
