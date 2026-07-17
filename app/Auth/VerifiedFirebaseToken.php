<?php

declare(strict_types=1);

namespace App\Auth;

use Carbon\CarbonImmutable;

final readonly class VerifiedFirebaseToken
{
    /**
     * @param  list<string>  $providerIds
     * @param  array<string, mixed>  $claims
     */
    public function __construct(
        public string $uid,
        public ?string $email,
        public bool $emailVerified,
        public array $providerIds,
        public CarbonImmutable $authenticatedAt,
        public array $claims,
    ) {}
}
