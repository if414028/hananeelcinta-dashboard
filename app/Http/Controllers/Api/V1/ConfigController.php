<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WebsiteSettings;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

final class ConfigController extends Controller
{
    public function __invoke(WebsiteSettings $settings): JsonResponse
    {
        $values = $settings->public();

        return ApiResponse::success(['church' => ['name' => $values['church_name'], 'tagline' => $values['church_tagline'], 'description' => $values['church_description'], 'address' => $values['church_address'], 'phone' => $values['church_phone'], 'whatsapp' => $values['church_whatsapp'], 'email' => $values['church_email'], 'service_schedule' => $values['church_service_schedule'], 'logo_url' => $values['logo'] ? Storage::disk('public')->url($values['logo']) : route('brand.logo')], 'social_media' => ['instagram' => $values['church_instagram'], 'youtube' => $values['church_youtube']], 'links' => ['website' => route('home'), 'maps' => $values['church_maps_url'], 'privacy_policy' => route('privacy')], 'mobile' => ['maintenance' => filter_var($values['mobile_maintenance_mode'], FILTER_VALIDATE_BOOL), 'minimum_version' => $values['mobile_minimum_version'] ?: null]]);
    }
}
