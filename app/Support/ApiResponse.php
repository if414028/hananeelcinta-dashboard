<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApiResponse
{
    public static function success(mixed $data = null, string $message = 'Data retrieved successfully.', int $status = 200, array $meta = []): JsonResponse
    {
        $payload = ['success' => true, 'message' => $message, 'data' => $data];
        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /** @param class-string<JsonResource> $resource */
    public static function paginated(LengthAwarePaginator $paginator, string $resource, Request $request, string $message = 'Data retrieved successfully.'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resource::collection($paginator->getCollection())->resolve($request),
            'meta' => ['current_page' => $paginator->currentPage(), 'last_page' => $paginator->lastPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()],
            'links' => ['first' => $paginator->url(1), 'last' => $paginator->url($paginator->lastPage()), 'prev' => $paginator->previousPageUrl(), 'next' => $paginator->nextPageUrl()],
        ]);
    }

    public static function error(string $message, int $status, array $errors = []): JsonResponse
    {
        $payload = ['success' => false, 'message' => $message];
        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
