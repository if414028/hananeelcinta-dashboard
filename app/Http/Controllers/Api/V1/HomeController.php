<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AnnouncementResource;
use App\Http\Resources\Api\V1\FamilyAltarResource;
use App\Http\Resources\Api\V1\PastorMessageResource;
use App\Models\Announcement;
use App\Models\FamilyAltar;
use App\Models\PastorMessage;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'featured_announcements' => AnnouncementResource::collection(Announcement::query()->published()->orderByDesc('is_featured')->latest('published_at')->limit(5)->get())->resolve($request),
            'latest_pastor_messages' => PastorMessageResource::collection(PastorMessage::query()->published()->orderByDesc('is_featured')->latest('published_at')->limit(5)->get())->resolve($request),
            'family_altars' => FamilyAltarResource::collection(FamilyAltar::query()->active()->orderBy('sort_order')->limit(10)->get())->resolve($request),
        ]);
    }
}
