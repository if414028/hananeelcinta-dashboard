<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class PhaseSevenFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_added_and_admin_pages_are_not_cached_or_indexed(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_hsts_is_only_added_for_secure_requests_when_enabled(): void
    {
        config(['security.hsts.enabled' => true]);

        $this->get('https://localhost/')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_public_config_and_home_api_have_cache_headers(): void
    {
        $this->getJson('/api/v1/config')->assertOk()->assertHeader('Cache-Control');
        $this->getJson('/api/v1/home')->assertOk()->assertHeader('Cache-Control');
    }

    public function test_content_changes_invalidate_public_and_api_caches(): void
    {
        Cache::put('public.home.v2', ['stale' => true], now()->addHour());
        Cache::put('api.home.v1', ['stale' => true], now()->addHour());
        Cache::put('public.sitemap.v1', ['stale' => true], now()->addHour());

        Announcement::factory()->create();

        $this->assertFalse(Cache::has('public.home.v2'));
        $this->assertFalse(Cache::has('api.home.v1'));
        $this->assertFalse(Cache::has('public.sitemap.v1'));
    }
}
