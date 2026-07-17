<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Auth\Contracts\FirebaseTokenVerifier;
use App\Auth\GoogleFirebaseTokenVerifier;
use App\Auth\VerifiedFirebaseToken;
use App\Exceptions\FirebaseAuthUnavailableException;
use App\Exceptions\FirebaseTokenException;
use App\Models\Congregation;
use App\Models\MobileAccount;
use Carbon\CarbonImmutable;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class PhaseSixPointFiveAuthBridgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'firebase.auth.project_id' => 'test-project',
            'firebase.auth.public_keys_url' => 'https://keys.example.test/firebase',
            'firebase.auth.public_keys_cache_ttl' => 3600,
        ]);
        Cache::flush();
    }

    public function test_google_firebase_verifier_checks_signature_claims_and_caches_certificate(): void
    {
        [$privateKey, $certificate] = $this->rsaCertificate();
        Http::fake([
            'https://keys.example.test/firebase' => Http::response(
                ['test-kid' => $certificate],
                200,
                ['Cache-Control' => 'public, max-age=3600'],
            ),
        ]);
        $now = now()->timestamp;
        $token = JWT::encode([
            'aud' => 'test-project',
            'iss' => 'https://securetoken.google.com/test-project',
            'sub' => 'firebase-uid',
            'exp' => $now + 3600,
            'iat' => $now - 10,
            'auth_time' => $now - 20,
            'email' => 'USER@example.com',
            'email_verified' => true,
            'firebase' => ['sign_in_provider' => 'password', 'identities' => ['email' => ['USER@example.com']]],
        ], $privateKey, 'RS256', 'test-kid');

        $verifier = app(GoogleFirebaseTokenVerifier::class);
        $verified = $verifier->verify($token);
        $verifier->verify($token);

        $this->assertSame('firebase-uid', $verified->uid);
        $this->assertSame('user@example.com', $verified->email);
        $this->assertTrue($verified->emailVerified);
        $this->assertContains('password', $verified->providerIds);
        Http::assertSentCount(1);
    }

    public function test_google_firebase_verifier_rejects_wrong_audience(): void
    {
        [$privateKey, $certificate] = $this->rsaCertificate();
        Http::fake(['https://keys.example.test/firebase' => Http::response(['test-kid' => $certificate])]);
        $now = now()->timestamp;
        $token = JWT::encode([
            'aud' => 'wrong-project',
            'iss' => 'https://securetoken.google.com/test-project',
            'sub' => 'firebase-uid',
            'exp' => $now + 3600,
            'iat' => $now - 10,
            'auth_time' => $now - 20,
        ], $privateKey, 'RS256', 'test-kid');

        $this->expectException(FirebaseTokenException::class);
        app(GoogleFirebaseTokenVerifier::class)->verify($token);
    }

    public function test_valid_firebase_token_creates_mobile_session_and_returns_safe_profile(): void
    {
        $congregation = Congregation::factory()->create([
            'legacy_firebase_uid' => 'linked-uid',
            'email' => 'profile@example.com',
            'notes' => 'Internal family information',
        ]);
        $this->fakeVerifiedToken('linked-uid', 'firebase@example.com');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer valid-token',
            'X-App-Platform' => 'android',
            'X-App-Version' => '2.4.1',
        ])->postJson('/api/v1/auth/session');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.account.uid', 'linked-uid')
            ->assertJsonPath('data.account.email', 'firebase@example.com')
            ->assertJsonPath('data.profile.id', $congregation->id)
            ->assertJsonPath('data.profile.member_number', $congregation->member_number)
            ->assertJsonMissing(['notes' => 'Internal family information'])
            ->assertJsonMissing(['legacy_firebase_uid' => 'linked-uid']);

        $this->assertDatabaseHas('mobile_accounts', [
            'congregation_id' => $congregation->id,
            'firebase_uid' => 'linked-uid',
            'email' => 'firebase@example.com',
            'last_platform' => 'android',
            'last_app_version' => '2.4.1',
            'is_active' => true,
        ]);

        $this->withToken('valid-token')
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.profile.full_name', $congregation->full_name);
    }

    public function test_missing_invalid_and_unlinked_tokens_are_rejected(): void
    {
        $this->postJson('/api/v1/auth/session')
            ->assertUnauthorized()
            ->assertJsonPath('success', false);

        $this->app->instance(FirebaseTokenVerifier::class, new class implements FirebaseTokenVerifier
        {
            public function verify(string $token): VerifiedFirebaseToken
            {
                throw new FirebaseTokenException('Invalid token');
            }
        });
        $this->withToken('invalid-token')->getJson('/api/v1/me')->assertUnauthorized();

        $this->fakeVerifiedToken('unknown-uid');
        $this->withToken('valid-but-unlinked')->getJson('/api/v1/me')->assertForbidden();
    }

    public function test_inactive_mobile_account_is_forbidden(): void
    {
        $congregation = Congregation::factory()->create(['legacy_firebase_uid' => 'inactive-uid']);
        MobileAccount::factory()->create([
            'congregation_id' => $congregation->id,
            'firebase_uid' => 'inactive-uid',
            'is_active' => false,
        ]);
        $this->fakeVerifiedToken('inactive-uid');

        $this->withToken('valid-token')->getJson('/api/v1/me')->assertForbidden();
    }

    public function test_firebase_public_key_outage_returns_service_unavailable(): void
    {
        $this->app->instance(FirebaseTokenVerifier::class, new class implements FirebaseTokenVerifier
        {
            public function verify(string $token): VerifiedFirebaseToken
            {
                throw new FirebaseAuthUnavailableException('Public keys unavailable');
            }
        });

        $this->withToken('unverifiable-token')
            ->getJson('/api/v1/me')
            ->assertServiceUnavailable()
            ->assertJsonPath('success', false);
    }

    public function test_mobile_account_sync_is_idempotent_and_supports_dry_run(): void
    {
        Congregation::factory()->create(['legacy_firebase_uid' => 'uid-one']);
        Congregation::factory()->create(['legacy_firebase_uid' => 'uid-two']);
        Congregation::factory()->create(['legacy_firebase_uid' => null]);

        $this->artisan('mobile-accounts:sync', ['--dry-run' => true])
            ->expectsOutputToContain('Mobile account dry-run completed.')
            ->assertSuccessful();
        $this->assertDatabaseCount('mobile_accounts', 0);

        $this->artisan('mobile-accounts:sync')->assertSuccessful();
        $this->artisan('mobile-accounts:sync')->assertSuccessful();

        $this->assertDatabaseCount('mobile_accounts', 2);
    }

    private function fakeVerifiedToken(string $uid, ?string $email = 'user@example.com'): void
    {
        $verified = new VerifiedFirebaseToken(
            uid: $uid,
            email: $email,
            emailVerified: true,
            providerIds: ['password'],
            authenticatedAt: CarbonImmutable::now(),
            claims: [],
        );
        $this->app->instance(FirebaseTokenVerifier::class, new class($verified) implements FirebaseTokenVerifier
        {
            public function __construct(private readonly VerifiedFirebaseToken $verified) {}

            public function verify(string $token): VerifiedFirebaseToken
            {
                return $this->verified;
            }
        });
    }

    /** @return array{0:string,1:string} */
    private function rsaCertificate(): array
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->assertNotFalse($key);
        $privateKey = '';
        $this->assertTrue(openssl_pkey_export($key, $privateKey));
        $csr = openssl_csr_new(['commonName' => 'firebase-test'], $key, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($csr);
        $certificateResource = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
        $this->assertNotFalse($certificateResource);
        $certificate = '';
        $this->assertTrue(openssl_x509_export($certificateResource, $certificate));

        return [$privateKey, $certificate];
    }
}
