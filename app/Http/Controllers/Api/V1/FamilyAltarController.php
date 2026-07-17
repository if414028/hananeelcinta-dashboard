<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FamilyAltarIndexRequest;
use App\Http\Resources\Api\V1\FamilyAltarResource;
use App\Models\FamilyAltar;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

final class FamilyAltarController extends Controller
{
    public function index(FamilyAltarIndexRequest $request): JsonResponse
    {
        $items = FamilyAltar::query()->active()->when($request->filled('day'), fn ($query) => $query->where('day_of_week', $request->day))->when($request->filled('city'), fn ($query) => $query->where('city', $request->city))->when($request->filled('search'), fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$request->search.'%')->orWhere('description', 'like', '%'.$request->search.'%')->orWhere('pic_name', 'like', '%'.$request->search.'%')))->orderBy('sort_order')->orderBy('name')->paginate($request->integer('per_page', 15))->withQueryString();

        return ApiResponse::paginated($items, FamilyAltarResource::class, $request);
    }

    public function show(FamilyAltar $familyAltar): JsonResponse
    {
        abort_unless($familyAltar->is_active, 404);

        return ApiResponse::success((new FamilyAltarResource($familyAltar))->resolve(request()));
    }
}
