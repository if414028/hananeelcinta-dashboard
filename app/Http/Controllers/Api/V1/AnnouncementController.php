<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AnnouncementIndexRequest;
use App\Http\Resources\Api\V1\AnnouncementResource;
use App\Models\Announcement;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AnnouncementController extends Controller
{
    public function index(AnnouncementIndexRequest $request): JsonResponse
    {
        $items = Announcement::query()->published()->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$request->search.'%')->orWhere('description', 'like', '%'.$request->search.'%')))->when($request->filled('featured'), fn ($query) => $query->where('is_featured', $request->boolean('featured')))->when($request->filled('published_after'), fn ($query) => $query->where('published_at', '>=', $request->date('published_after')))->when($request->filled('published_before'), fn ($query) => $query->where('published_at', '<=', $request->date('published_before')->endOfDay()))->orderByDesc('is_featured')->latest('published_at')->paginate($request->integer('per_page', 15))->withQueryString();

        return ApiResponse::paginated($items, AnnouncementResource::class, $request);
    }

    public function show(Announcement $announcement): JsonResponse
    {
        abort_unless(Announcement::query()->published()->whereKey($announcement)->exists(), 404);

        return ApiResponse::success((new AnnouncementResource($announcement))->resolve(request()));
    }
}
