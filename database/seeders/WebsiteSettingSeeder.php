<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

final class WebsiteSettingSeeder extends Seeder
{
    /** @var array<string, array{string, string, string, bool}> */
    private const SETTINGS = [
        'church_name' => ['general', 'JKI Hananeel Cinta', 'text', true],
        'church_tagline' => ['general', 'Rumah untuk bertumbuh dalam iman, pengharapan, dan kasih.', 'text', true],
        'church_description' => ['church_profile', '', 'textarea', true],
        'church_vision' => ['church_profile', '', 'textarea', true],
        'church_mission' => ['church_profile', '', 'textarea', true],
        'church_history' => ['church_profile', '', 'richtext', true],
        'church_address' => ['contact', '', 'textarea', true],
        'church_phone' => ['contact', '', 'text', true],
        'church_whatsapp' => ['contact', '', 'text', true],
        'church_email' => ['contact', '', 'email', true],
        'church_instagram' => ['social_media', '', 'url', true],
        'church_youtube' => ['social_media', '', 'url', true],
        'church_maps_url' => ['contact', '', 'url', true],
        'church_service_schedule' => ['church_profile', '', 'textarea', true],
        'logo' => ['branding', '', 'image', true],
        'favicon' => ['branding', '', 'image', true],
        'hero_image' => ['branding', '', 'image', true],
        'footer_description' => ['general', '', 'textarea', true],
        'privacy_policy' => ['legal', '', 'richtext', true],
        'seo_title' => ['seo', 'JKI Hananeel Cinta', 'text', true],
        'seo_description' => ['seo', '', 'textarea', true],
        'mobile_maintenance_mode' => ['mobile_api', '0', 'boolean', true],
        'mobile_minimum_version' => ['mobile_api', '', 'text', true],
    ];

    public function run(): void
    {
        foreach (self::SETTINGS as $key => [$group, $value, $type, $isPublic]) {
            WebsiteSetting::query()->updateOrCreate(['key' => $key], compact('group', 'value', 'type') + ['is_public' => $isPublic]);
        }
    }
}
