<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Auth\Contracts\FirebaseTokenVerifier;
use App\Auth\VerifiedFirebaseToken;
use App\Models\Congregation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CongregationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_existing_congregation_can_become_admin_and_receive_mobile_role(): void
    {
        $manager = $this->manager();
        $congregation = Congregation::factory()->create(['legacy_firebase_uid' => 'member-uid']);

        $this->actingAs($manager)->get(route('admin.admin-users.create'))
            ->assertOk()->assertSee('Hubungkan ke jemaat')->assertSee($congregation->member_number);
        $this->post(route('admin.admin-users.store'), $this->payload($congregation))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.admin-users.index'));

        $admin = User::query()->where('congregation_id', $congregation->id)->sole();
        $this->assertTrue($admin->hasRole('Admin'));
        $this->assertTrue(Hash::check('cms-password-123', $admin->password));
        $this->assertDatabaseCount('congregations', 1);
        $this->get(route('admin.admin-users.index'))->assertOk()->assertSee($congregation->member_number);
        $this->get(route('admin.admin-users.show', $admin))->assertOk()->assertSee($congregation->member_number);

        $this->verifyToken('member-uid');
        $this->withToken('valid-token')->postJson('/api/v1/auth/session')
            ->assertOk()->assertJsonPath('data.profile.role', 'SuperUser');
        $this->withToken('valid-token')->getJson('/api/v1/me')
            ->assertOk()->assertJsonPath('data.profile.role', 'SuperUser');

        $this->post(route('admin.logout'));
        $this->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'cms-password-123'])
            ->assertRedirect(route('admin.congregations.index'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_existing_admin_can_link_change_and_unlink_a_congregation_without_changing_password(): void
    {
        $manager = $this->manager();
        $admin = User::factory()->create(['password' => 'original-password']);
        $admin->assignRole('Admin');
        $first = Congregation::factory()->create();
        $second = Congregation::factory()->create();
        $payload = ['name' => $admin->name, 'email' => $admin->email, 'role' => 'Admin', 'is_active' => '1'];

        $this->actingAs($manager)->put(route('admin.admin-users.update', $admin), $payload + ['congregation_id' => $first->id])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->get(route('admin.admin-users.edit', $admin))->assertOk()->assertSee($first->member_number);
        $this->assertSame('SuperUser', $first->mobileRole());
        $this->assertTrue(Hash::check('original-password', $admin->fresh()->password));

        $this->put(route('admin.admin-users.update', $admin), $payload + ['congregation_id' => $second->id])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('User', $first->mobileRole());
        $this->assertSame('SuperUser', $second->mobileRole());

        $this->put(route('admin.admin-users.update', $admin), $payload + ['congregation_id' => ''])
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull($admin->fresh()->congregation_id);
        $this->assertSame('User', $second->mobileRole());
    }

    public function test_inactive_or_deleted_admin_loses_mobile_role_and_deleted_link_can_be_reused(): void
    {
        $manager = $this->manager();
        $congregation = Congregation::factory()->create(['legacy_firebase_uid' => 'revoked-uid']);
        $admin = User::factory()->create(['congregation_id' => $congregation->id]);
        $admin->assignRole('Admin');
        $this->verifyToken('revoked-uid');
        $this->withToken('valid-token')->getJson('/api/v1/me')->assertJsonPath('data.profile.role', 'SuperUser');

        $this->actingAs($manager)->put(route('admin.admin-users.update', $admin), [
            'congregation_id' => $congregation->id, 'name' => $admin->name, 'email' => $admin->email, 'role' => 'Admin', 'is_active' => '0',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->withToken('valid-token')->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.profile.role', 'User');

        $admin->update(['is_active' => true]);
        $this->delete(route('admin.admin-users.destroy', $admin))->assertRedirect();
        $this->assertSoftDeleted($admin);
        $this->assertNull($admin->fresh()->congregation_id);
        $this->withToken('valid-token')->postJson('/api/v1/auth/session')->assertOk()->assertJsonPath('data.profile.role', 'User');
        $this->post(route('admin.admin-users.store'), $this->payload($congregation))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->withToken('valid-token')->getJson('/api/v1/me')->assertJsonPath('data.profile.role', 'SuperUser');
    }

    public function test_legacy_notes_matching_email_and_roleless_account_do_not_grant_mobile_admin(): void
    {
        $congregation = Congregation::factory()->create([
            'legacy_firebase_uid' => 'ordinary-uid', 'notes' => 'Role Firebase: SuperUser',
        ]);
        $unlinked = User::factory()->create(['email' => $congregation->email]);
        $unlinked->assignRole('Super Admin');
        $linked = User::factory()->create(['congregation_id' => $congregation->id]);
        $this->verifyToken('ordinary-uid');

        $this->withToken('valid-token')->postJson('/api/v1/auth/session', ['role' => 'SuperUser'])
            ->assertOk()->assertJsonPath('data.profile.role', 'User');
        foreach (['Super Admin', 'Admin', 'Pastor'] as $role) {
            $linked->syncRoles($role);
            $this->withToken('valid-token')->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.profile.role', 'SuperUser');
        }
        $linked->syncRoles([]);
        $this->withToken('valid-token')->getJson('/api/v1/me')->assertJsonPath('data.profile.role', 'User');
        $linked->delete();
        $this->withToken('valid-token')->getJson('/api/v1/me')->assertJsonPath('data.profile.role', 'User');
    }

    public function test_duplicate_missing_and_deleted_congregations_cannot_be_linked(): void
    {
        $manager = $this->manager();
        $congregation = Congregation::factory()->create();
        $admin = User::factory()->create(['congregation_id' => $congregation->id]);
        $admin->assignRole('Admin');
        $other = User::factory()->create();
        $other->assignRole('Admin');
        $payload = $this->payload($congregation);

        $this->actingAs($manager)->get(route('admin.admin-users.create'))
            ->assertOk()->assertDontSee($congregation->member_number);
        $this->post(route('admin.admin-users.store'), $payload)->assertSessionHasErrors('congregation_id');
        $this->put(route('admin.admin-users.update', $other), array_merge($payload, ['name' => $other->name, 'email' => $other->email]))
            ->assertSessionHasErrors('congregation_id');
        $this->post(route('admin.admin-users.store'), array_merge($payload, ['congregation_id' => 999999]))
            ->assertSessionHasErrors('congregation_id');
        $congregation->delete();
        $this->post(route('admin.admin-users.store'), $payload)->assertSessionHasErrors('congregation_id');
        $this->assertDatabaseCount('users', 3);
    }

    public function test_admin_without_management_permission_cannot_promote_a_congregation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $congregation = Congregation::factory()->create();

        $this->actingAs($user)->get(route('admin.admin-users.create'))->assertForbidden();
        $this->post(route('admin.admin-users.store'), $this->payload($congregation))->assertForbidden();
        $this->assertSame('User', $congregation->mobileRole());
    }

    public function test_standalone_admin_creation_remains_supported(): void
    {
        $congregation = Congregation::factory()->create();
        $payload = $this->payload($congregation);
        unset($payload['congregation_id']);

        $this->actingAs($this->manager())->post(route('admin.admin-users.store'), $payload)
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertNull(User::query()->where('email', $congregation->email)->sole()->congregation_id);
        $this->assertSame('User', $congregation->mobileRole());
    }

    private function manager(): User
    {
        $manager = User::factory()->create();
        $manager->assignRole('Super Admin');

        return $manager;
    }

    private function payload(Congregation $congregation): array
    {
        return [
            'congregation_id' => $congregation->id,
            'name' => $congregation->full_name,
            'email' => $congregation->email,
            'role' => 'Admin',
            'password' => 'cms-password-123',
            'password_confirmation' => 'cms-password-123',
            'is_active' => '1',
        ];
    }

    private function verifyToken(string $uid): void
    {
        $verified = new VerifiedFirebaseToken(
            uid: $uid, email: 'firebase@example.com', emailVerified: true,
            providerIds: ['password'], authenticatedAt: CarbonImmutable::now(), claims: [],
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
}
