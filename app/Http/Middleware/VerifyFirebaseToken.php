<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Contracts\FirebaseTokenVerifier;
use App\Exceptions\FirebaseAuthUnavailableException;
use App\Exceptions\FirebaseTokenException;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class VerifyFirebaseToken
{
    public function __construct(private readonly FirebaseTokenVerifier $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! is_string($token) || $token === '') {
            return ApiResponse::error('Firebase ID token is required.', 401);
        }

        try {
            $verified = $this->verifier->verify($token);
        } catch (FirebaseTokenException) {
            return ApiResponse::error('Firebase ID token is invalid or account is unavailable.', 401);
        } catch (FirebaseAuthUnavailableException) {
            return ApiResponse::error('Authentication service is temporarily unavailable.', 503);
        } catch (Throwable $exception) {
            report($exception);

            return ApiResponse::error('Authentication service is temporarily unavailable.', 503);
        }

        $request->attributes->set('firebase_token', $verified);

        return $next($request);
    }
}
