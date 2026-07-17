<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\GeneratePrayerReference;
use App\Enums\PrayerRequestSource;
use App\Enums\PrayerRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePrayerRequest;
use App\Models\PrayerRequest;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class PrayerRequestController extends Controller
{
    public function __invoke(StorePrayerRequest $request, GeneratePrayerReference $generator): JsonResponse
    {
        $prayer = DB::transaction(function () use ($request, $generator): PrayerRequest {
            $source = match ($request->string('client_platform')->toString()) {
                'android' => PrayerRequestSource::Android, 'ios' => PrayerRequestSource::Ios, default => PrayerRequestSource::Website
            };

            return PrayerRequest::query()->create(array_merge($request->safe()->except(['privacy_accepted', 'client_platform']), ['reference_number' => $generator->handle(), 'is_anonymous' => $request->boolean('is_anonymous'), 'is_confidential' => $request->boolean('is_confidential'), 'status' => PrayerRequestStatus::New, 'source' => $source, 'ip_address' => $request->ip(), 'user_agent' => mb_substr((string) $request->userAgent(), 0, 1000)]));
        });

        return ApiResponse::success(['reference_number' => $prayer->reference_number, 'status' => $prayer->status->value, 'submitted_at' => $prayer->created_at->toAtomString()], 'Prayer request submitted successfully.', 201);
    }
}
