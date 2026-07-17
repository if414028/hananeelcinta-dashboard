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
use Illuminate\Support\Facades\Cache;

final class HomeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $cached = Cache::remember('api.home.v1', now()->addMinutes(10), fn (): array => [
            'announcements' => Announcement::query()->published()->orderByDesc('is_featured')->latest('published_at')->limit(5)->get()->map(fn (Announcement $item): array => $item->getAttributes())->all(),
            'pastorMessages' => PastorMessage::query()->published()->orderByDesc('is_featured')->latest('published_at')->limit(5)->get()->map(fn (PastorMessage $item): array => $item->getAttributes())->all(),
            'familyAltars' => FamilyAltar::query()->active()->orderBy('sort_order')->limit(10)->get()->map(fn (FamilyAltar $item): array => $item->getAttributes())->all(),
        ]);

        return ApiResponse::success([
            'featured_announcements' => AnnouncementResource::collection(Announcement::hydrate($cached['announcements'] ?? []))->resolve($request),
            'latest_pastor_messages' => PastorMessageResource::collection(PastorMessage::hydrate($cached['pastorMessages'] ?? []))->resolve($request),
            'family_altars' => FamilyAltarResource::collection(FamilyAltar::hydrate($cached['familyAltars'] ?? []))->resolve($request),
        ]);
    }
}
