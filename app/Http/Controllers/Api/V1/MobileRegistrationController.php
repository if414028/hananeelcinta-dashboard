<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\GenerateMemberNumber;
use App\Auth\VerifiedFirebaseToken;
use App\Enums\BaptismStatus;
use App\Enums\CongregationMembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterCongregationRequest;
use App\Http\Resources\Api\V1\MobileAccountResource;
use App\Models\Congregation;
use App\Models\MobileAccount;
use App\Services\ImageUploadService;
use App\Support\ApiResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

final class MobileRegistrationController extends Controller
{
    public function __invoke(RegisterCongregationRequest $request, GenerateMemberNumber $generator, ImageUploadService $uploads): JsonResponse
    {
        /** @var VerifiedFirebaseToken $verified */
        $verified = $request->attributes->get('firebase_token');

        // Include deleted records: registration must never revive or replace a disabled identity.
        if (Congregation::withTrashed()->where('legacy_firebase_uid', $verified->uid)->exists()
            || MobileAccount::withTrashed()->where('firebase_uid', $verified->uid)->exists()) {
            return ApiResponse::error('Firebase account is already registered. Use the profile endpoint or contact the church administrator.', 409);
        }
        if ($verified->email !== null && Congregation::withTrashed()->where('email', $verified->email)->exists()) {
            return ApiResponse::error('Email is already associated with congregation data. Contact the church administrator to link your account.', 409);
        }

        $photoPath = null;
        try {
            // Store once, outside the retried database transaction, and clean up on rollback.
            if ($request->hasFile('profile_photo')) {
                $photoPath = $uploads->store($request->file('profile_photo'), 'congregations');
            }
            $profile = $request->safe()->except('profile_photo');

            $account = DB::transaction(function () use ($request, $verified, $generator, $profile, $photoPath): MobileAccount {
                $congregation = Congregation::query()->create(array_merge($profile, [
                    'profile_photo' => $photoPath,
                    'legacy_firebase_uid' => $verified->uid,
                    'email' => $verified->email,
                    'member_number' => $generator->handle(),
                    'baptism_status' => $request->validated('baptism_status', BaptismStatus::Unknown->value),
                    'membership_status' => CongregationMembershipStatus::Visitor,
                    'joined_at' => today(),
                    'is_active' => true,
                ]));
                $platform = mb_strtolower((string) $request->header('X-App-Platform'));
                $account = MobileAccount::query()->create([
                    'congregation_id' => $congregation->id,
                    'firebase_uid' => $verified->uid,
                    'email' => $verified->email,
                    'email_verified_at' => $verified->emailVerified ? now() : null,
                    'provider_ids' => $verified->providerIds,
                    'is_active' => true,
                    'last_authenticated_at' => $verified->authenticatedAt,
                    'last_seen_at' => now(),
                    'last_login_ip' => $request->ip(),
                    'last_platform' => in_array($platform, ['android', 'ios'], true) ? $platform : null,
                    'last_app_version' => mb_substr(trim((string) $request->header('X-App-Version')), 0, 40) ?: null,
                ]);

                return $account->setRelation('congregation', $congregation);
            }, 3);
        } catch (Throwable $exception) {
            $uploads->delete($photoPath);
            if (! $exception instanceof UniqueConstraintViolationException) {
                throw $exception;
            }

            // Database uniqueness also protects simultaneous submissions; the transaction rolls back both records.
            return ApiResponse::error('Account or congregation data is already registered. Retrieve your profile or contact the church administrator.', 409);
        }

        return ApiResponse::success((new MobileAccountResource($account))->resolve($request), 'Congregation registered successfully.', 201);
    }
}
