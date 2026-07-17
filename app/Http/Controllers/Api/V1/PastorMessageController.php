<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PastorMessageIndexRequest;
use App\Http\Resources\Api\V1\PastorMessageResource;
use App\Models\PastorMessage;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PastorMessageController extends Controller
{
    public function index(PastorMessageIndexRequest $request): JsonResponse
    {
        $items = PastorMessage::query()->published()->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$request->search.'%')->orWhere('content', 'like', '%'.$request->search.'%')))->when($request->filled('writer'), fn ($query) => $query->where('writer', $request->writer))->when($request->filled('featured'), fn ($query) => $query->where('is_featured', $request->boolean('featured')))->when($request->filled('published_after'), fn ($query) => $query->where('published_at', '>=', $request->date('published_after')))->when($request->filled('published_before'), fn ($query) => $query->where('published_at', '<=', $request->date('published_before')->endOfDay()))->orderByDesc('is_featured')->latest('published_at')->paginate($request->integer('per_page', 15))->withQueryString();

        return ApiResponse::paginated($items, PastorMessageResource::class, $request);
    }

    public function show(PastorMessage $pastorMessage): JsonResponse
    {
        abort_unless(PastorMessage::query()->published()->whereKey($pastorMessage)->exists(), 404);
        PastorMessage::query()->whereKey($pastorMessage)->increment('view_count');
        $pastorMessage->view_count++;

        return ApiResponse::success((new PastorMessageResource($pastorMessage))->resolve(request()));
    }
}
