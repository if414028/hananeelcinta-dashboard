<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileAccountResource;
use App\Models\MobileAccount;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MobileSessionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        return ApiResponse::success($this->payload($request), 'Firebase session authenticated.');
    }

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success($this->payload($request), 'Mobile profile retrieved.');
    }

    /** @return array<string, mixed> */
    private function payload(Request $request): array
    {
        /** @var MobileAccount $account */
        $account = $request->attributes->get('mobile_account');

        return (new MobileAccountResource($account))->resolve($request);
    }
}
