<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AdminUserPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_other_admins_cannot_change_a_super_admin_password(): void
    {
        $target = $this->user('Super Admin');
        $originalPassword = $target->password;

        foreach (['Admin', 'Super Admin'] as $role) {
            $actor = $this->user($role);
            $actor->givePermissionTo('admins.update');

            $this->actingAs($actor)->get(route('admin.admin-users.edit', $target))
                ->assertOk()
                ->assertDontSee('name="password"', false)
                ->assertDontSee('name="password_confirmation"', false)
                ->assertSee('Password Super Admin hanya dapat diubah oleh pemilik akun.');

            foreach (['Super Admin', 'Admin'] as $requestedRole) {
                $this->actingAs($actor)->put(route('admin.admin-users.update', $target),
                    array_replace($this->payload($target), ['role' => $requestedRole]))
                    ->assertForbidden();

                $this->assertSame($originalPassword, $target->fresh()->password);
                $this->assertTrue($target->fresh()->hasRole('Super Admin'));
            }
        }
    }

    public function test_super_admin_can_change_their_own_password(): void
    {
        $admin = $this->user('Super Admin');

        $this->actingAs($admin)->get(route('admin.admin-users.edit', $admin))
            ->assertOk()->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false);
        $this->put(route('admin.admin-users.update', $admin), $this->payload($admin))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.admin-users.index'));

        $this->assertTrue(Hash::check('new-cms-password-123', $admin->fresh()->password));
    }

    public function test_super_admin_can_edit_other_super_admin_details_without_changing_password(): void
    {
        $actor = $this->user('Super Admin');
        $target = $this->user('Super Admin');
        $originalPassword = $target->password;

        foreach ([[], ['password' => '', 'password_confirmation' => '']] as $passwordFields) {
            $payload = array_replace($this->payload($target), ['name' => 'Nama diperbarui']);
            unset($payload['password'], $payload['password_confirmation']);

            $this->actingAs($actor)->put(route('admin.admin-users.update', $target), $payload + $passwordFields)
                ->assertSessionHasNoErrors()->assertRedirect(route('admin.admin-users.index'));

            $this->assertSame('Nama diperbarui', $target->fresh()->name);
            $this->assertSame($originalPassword, $target->fresh()->password);
        }
    }

    public function test_super_admin_can_still_change_an_admin_password(): void
    {
        $actor = $this->user('Super Admin');
        $target = $this->user('Admin');

        $this->actingAs($actor)->get(route('admin.admin-users.edit', $target))
            ->assertOk()->assertSee('name="password"', false);
        $this->put(route('admin.admin-users.update', $target), $this->payload($target))
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.admin-users.index'));

        $this->assertTrue(Hash::check('new-cms-password-123', $target->fresh()->password));
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function payload(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            'is_active' => '1',
            'password' => 'new-cms-password-123',
            'password_confirmation' => 'new-cms-password-123',
        ];
    }
}
