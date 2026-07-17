<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    public function test_guest_is_redirected_to_admin_login_instead_of_server_error(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
    }

    public function test_forgot_password_notification_uses_admin_reset_route(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('admin.password.email'), ['email' => $user->email])
            ->assertSessionHas('success');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            return str_contains((string) $mail->actionUrl, '/admin/reset-password/')
                && str_contains((string) $mail->actionUrl, urlencode($user->email));
        });
    }
}
