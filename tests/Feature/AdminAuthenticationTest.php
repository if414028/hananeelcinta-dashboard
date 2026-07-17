<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_active_admin_can_login_and_reach_dashboard(): void
    {
        $user = User::factory()->create(['password' => 'secret-password']);
        $user->assignRole('Admin');

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.dashboard'))->assertOk();
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_inactive_admin_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false, 'password' => 'secret-password']);
        $user->assignRole('Admin');

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post(route('admin.login.store'), [
                'email' => $user->email,
                'password' => 'incorrect',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'incorrect',
        ])->assertTooManyRequests();
    }
}
