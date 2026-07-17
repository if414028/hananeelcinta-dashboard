<?php

declare(strict_types=1);

namespace App\Auth;

use App\Auth\Contracts\FirebaseTokenVerifier;
use App\Exceptions\FirebaseAuthUnavailableException;
use App\Exceptions\FirebaseTokenException;
use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

final class GoogleFirebaseTokenVerifier implements FirebaseTokenVerifier
{
    public function verify(string $token): VerifiedFirebaseToken
    {
        $projectId = trim((string) config('firebase.auth.project_id'));
        if ($projectId === '') {
            throw new FirebaseTokenException('Firebase authentication is not configured.');
        }

        try {
            $kid = $this->tokenKeyId($token);
            $certificates = $this->certificates($kid);
            $keys = [];
            foreach ($certificates as $certificateKid => $certificate) {
                $keys[$certificateKid] = new Key($certificate, 'RS256');
            }

            $headers = new \stdClass;
            $payload = JWT::decode($token, $keys, $headers);
            if (($headers->alg ?? null) !== 'RS256' || ($headers->kid ?? null) !== $kid) {
                throw new FirebaseTokenException('Firebase token header is invalid.');
            }

            $claims = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
            $this->validateClaims($claims, $projectId);

            $firebase = is_array($claims['firebase'] ?? null) ? $claims['firebase'] : [];
            $identities = is_array($firebase['identities'] ?? null) ? array_diff(array_keys($firebase['identities']), ['email']) : [];
            $signInProvider = is_string($firebase['sign_in_provider'] ?? null) ? $firebase['sign_in_provider'] : null;
            $providers = array_values(array_unique(array_filter([...$identities, $signInProvider])));

            return new VerifiedFirebaseToken(
                uid: (string) $claims['sub'],
                email: is_string($claims['email'] ?? null) ? mb_strtolower($claims['email']) : null,
                emailVerified: ($claims['email_verified'] ?? false) === true,
                providerIds: $providers,
                authenticatedAt: CarbonImmutable::createFromTimestamp((int) $claims['auth_time'], config('app.timezone')),
                claims: $claims,
            );
        } catch (FirebaseAuthUnavailableException|FirebaseTokenException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new FirebaseTokenException('Firebase ID token is invalid.', previous: $exception);
        }
    }

    /** @return array<string, string> */
    private function certificates(string $kid): array
    {
        $cacheKey = 'firebase.auth.public-certificates';
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && isset($cached[$kid]) && is_string($cached[$kid])) {
            return $cached;
        }

        $response = Http::acceptJson()
            ->timeout((int) config('firebase.auth.http_timeout', 5))
            ->retry(2, 100, throw: false)
            ->get((string) config('firebase.auth.public_keys_url'));

        if (! $response->successful()) {
            throw new FirebaseAuthUnavailableException('Firebase public keys are unavailable.');
        }

        $certificates = $response->json();
        if (! is_array($certificates) || ! isset($certificates[$kid])) {
            throw new FirebaseTokenException('Firebase token signing key is unknown.');
        }
        foreach ($certificates as $certificateKid => $certificate) {
            if (! is_string($certificateKid) || ! is_string($certificate) || ! str_contains($certificate, 'BEGIN CERTIFICATE')) {
                throw new FirebaseTokenException('Firebase public key response is invalid.');
            }
        }

        Cache::put($cacheKey, $certificates, now()->addSeconds($this->cacheTtl($response)));

        return $certificates;
    }

    private function tokenKeyId(string $token): string
    {
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            throw new FirebaseTokenException('Firebase ID token has an invalid format.');
        }

        try {
            $decoded = JWT::urlsafeB64Decode($segments[0]);
            $header = json_decode($decoded, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $header = null;
        }
        if (! is_array($header) || ($header['alg'] ?? null) !== 'RS256' || ! is_string($header['kid'] ?? null) || $header['kid'] === '') {
            throw new FirebaseTokenException('Firebase token header is invalid.');
        }

        return $header['kid'];
    }

    /** @param array<string, mixed> $claims */
    private function validateClaims(array $claims, string $projectId): void
    {
        $now = now()->timestamp;
        $leeway = max(0, (int) config('firebase.auth.leeway', 30));
        $uid = $claims['sub'] ?? null;
        if (! is_string($uid) || $uid === '' || mb_strlen($uid) > 128) {
            throw new FirebaseTokenException('Firebase token subject is invalid.');
        }
        if (($claims['aud'] ?? null) !== $projectId) {
            throw new FirebaseTokenException('Firebase token audience is invalid.');
        }
        if (($claims['iss'] ?? null) !== "https://securetoken.google.com/{$projectId}") {
            throw new FirebaseTokenException('Firebase token issuer is invalid.');
        }
        foreach (['exp', 'iat', 'auth_time'] as $claim) {
            if (! is_numeric($claims[$claim] ?? null)) {
                throw new FirebaseTokenException("Firebase token claim {$claim} is missing.");
            }
        }
        if ((int) $claims['exp'] <= $now - $leeway) {
            throw new FirebaseTokenException('Firebase ID token has expired.');
        }
        if ((int) $claims['iat'] > $now + $leeway || (int) $claims['auth_time'] > $now + $leeway) {
            throw new FirebaseTokenException('Firebase token time claims are invalid.');
        }
    }

    private function cacheTtl(Response $response): int
    {
        $fallback = max(60, (int) config('firebase.auth.public_keys_cache_ttl', 3600));
        $cacheControl = $response->header('Cache-Control');
        if (is_string($cacheControl) && preg_match('/max-age=(\d+)/', $cacheControl, $matches) === 1) {
            return max(60, min($fallback, (int) $matches[1]));
        }

        return $fallback;
    }
}
