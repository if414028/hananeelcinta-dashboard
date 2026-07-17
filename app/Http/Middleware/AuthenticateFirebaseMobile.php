<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Contracts\FirebaseTokenVerifier;
use App\Exceptions\FirebaseAuthUnavailableException;
use App\Exceptions\FirebaseTokenException;
use App\Exceptions\MobileAccountUnavailableException;
use App\Models\Congregation;
use App\Models\MobileAccount;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class AuthenticateFirebaseMobile
{
    public function __construct(private readonly FirebaseTokenVerifier $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();
        if (! is_string($bearerToken) || $bearerToken === '') {
            return ApiResponse::error('Firebase ID token is required.', 401);
        }

        try {
            $verified = $this->verifier->verify($bearerToken);
            $congregation = Congregation::query()->where('legacy_firebase_uid', $verified->uid)->first();
            if ($congregation === null) {
                return ApiResponse::error('Mobile account is not linked to congregation data.', 403);
            }
            if (! $congregation->is_active) {
                return ApiResponse::error('Mobile account is inactive.', 403);
            }

            $account = DB::transaction(function () use ($request, $verified, $congregation): MobileAccount {
                $byUid = MobileAccount::withTrashed()->lockForUpdate()->where('firebase_uid', $verified->uid)->first();
                $byCongregation = MobileAccount::withTrashed()->lockForUpdate()->where('congregation_id', $congregation->id)->first();
                if (($byUid !== null && $byUid->congregation_id !== $congregation->id)
                    || ($byCongregation !== null && $byCongregation->firebase_uid !== $verified->uid)) {
                    throw new MobileAccountUnavailableException('Firebase account mapping conflict.');
                }

                $account = $byUid ?? $byCongregation ?? new MobileAccount([
                    'congregation_id' => $congregation->id,
                    'firebase_uid' => $verified->uid,
                    'is_active' => true,
                ]);
                if ($account->trashed() || ! $account->is_active) {
                    throw new MobileAccountUnavailableException('Mobile account is inactive.');
                }

                $platform = mb_strtolower((string) $request->header('X-App-Platform'));
                $account->fill([
                    'email' => $verified->email ?? $account->email ?? $congregation->email,
                    'email_verified_at' => $verified->emailVerified ? ($account->email_verified_at ?? now()) : null,
                    'provider_ids' => $verified->providerIds,
                    'last_authenticated_at' => $verified->authenticatedAt,
                    'last_seen_at' => now(),
                    'last_login_ip' => $request->ip(),
                    'last_platform' => in_array($platform, ['android', 'ios'], true) ? $platform : null,
                    'last_app_version' => mb_substr(trim((string) $request->header('X-App-Version')), 0, 40) ?: null,
                ]);
                $account->save();

                return $account;
            });
        } catch (MobileAccountUnavailableException) {
            return ApiResponse::error('Mobile account is inactive or has a mapping conflict.', 403);
        } catch (FirebaseTokenException) {
            return ApiResponse::error('Firebase ID token is invalid or account is unavailable.', 401);
        } catch (FirebaseAuthUnavailableException) {
            return ApiResponse::error('Authentication service is temporarily unavailable.', 503);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Authentication service is temporarily unavailable.', 503);
        }

        $account->setRelation('congregation', $congregation);
        $request->attributes->set('mobile_account', $account);
        $request->attributes->set('congregation', $congregation);

        return $next($request);
    }
}
