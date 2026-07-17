<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MobileCongregationResource;
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

        return [
            'account' => [
                'id' => $account->id,
                'uid' => $account->firebase_uid,
                'email' => $account->email,
                'email_verified' => $account->email_verified_at !== null,
                'providers' => $account->provider_ids ?? [],
                'authenticated_at' => $account->last_authenticated_at?->toAtomString(),
            ],
            'profile' => (new MobileCongregationResource($account->congregation))->resolve($request),
        ];
    }
}
