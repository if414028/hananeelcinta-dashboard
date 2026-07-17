<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Cache;

final class WebsiteSettings
{
    /** @return array<string, string|null> */
    public function public(): array
    {
        return Cache::rememberForever('website_settings.public', fn (): array => array_replace([
            'church_name' => 'JKI Hananeel Cinta',
            'church_tagline' => '',
            'church_description' => '',
            'church_vision' => '',
            'church_mission' => '',
            'church_history' => '',
            'church_address' => '',
            'church_phone' => '',
            'church_whatsapp' => '',
            'church_email' => '',
            'church_instagram' => '',
            'church_youtube' => '',
            'church_maps_url' => '',
            'church_service_schedule' => '',
            'footer_description' => '',
            'privacy_policy' => '',
            'seo_title' => '',
            'seo_description' => '',
            'logo' => '',
            'favicon' => '',
            'hero_image' => '',
            'mobile_maintenance_mode' => '0',
            'mobile_minimum_version' => '',
        ], WebsiteSetting::query()->where('is_public', true)->pluck('value', 'key')->all()));
    }

    public function get(string $key, ?string $default = null): ?string
    {
        return $this->public()[$key] ?? $default;
    }

    public function forget(): void
    {
        Cache::forget('website_settings');
        Cache::forget('website_settings.public');
    }
}
