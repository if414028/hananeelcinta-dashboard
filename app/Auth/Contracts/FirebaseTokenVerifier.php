<?php

declare(strict_types=1);

namespace App\Auth\Contracts;

use App\Auth\VerifiedFirebaseToken;

interface FirebaseTokenVerifier
{
    public function verify(string $token): VerifiedFirebaseToken;
}
