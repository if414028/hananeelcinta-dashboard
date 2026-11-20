<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\WebsiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class AdminMenuAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, WebsiteSettingSeeder::class]);
    }

    public function test_admin_can_open_only_the_allowed_module_menus(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        foreach (['congregations', 'prayer-requests', 'family-altars', 'events', 'discipleship', 'announcements', 'pastor-messages'] as $module) {
            $this->actingAs($admin)->get(route('admin.'.$module.'.index'))->assertOk();
        }

        $response = $this->get(route('admin.congregations.index'));
        foreach (['Jemaat', 'Prayer Request', 'Mezbah Keluarga', 'Event', 'Pemuridan', 'Pengumuman', 'Pastor Message'] as $label) {
            $response->assertSee($label);
        }
        $response->assertSee('href="'.route('admin.dashboard').'"', false);
        $this->get(route('admin.dashboard'))->assertOk();
        foreach (['admin-users.index', 'roles.index', 'settings.index', 'audit-logs.index'] as $route) {
            $response->assertDontSee('href="'.route('admin.'.$route).'"', false);
            $this->get(route('admin.'.$route))->assertForbidden();
        }
        $this->get('/admin')->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_reserved_permissions_cannot_bypass_the_super_admin_requirement(): void
    {
        foreach (['Admin', 'Pastor'] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role);
            $user->givePermissionTo(['dashboard.view', 'admins.view', 'admins.create', 'admins.update', 'admins.delete', 'settings.view', 'settings.update', 'audit_logs.view']);
            $target = User::factory()->create();

            foreach (['admin-users.index', 'settings.index', 'audit-logs.index', 'roles.index'] as $route) {
                $this->actingAs($user)->get(route('admin.'.$route))->assertForbidden();
            }
            if ($role === 'Pastor') {
                $this->get(route('admin.dashboard'))->assertForbidden();
                $this->get(route('admin.prayer-requests.index'))->assertDontSee('href="'.route('admin.dashboard').'"', false);
            }
            $this->post(route('admin.admin-users.store'), [])->assertForbidden();
            $this->put(route('admin.admin-users.update', $target), [])->assertForbidden();
            $this->delete(route('admin.admin-users.destroy', $target))->assertForbidden();
            $this->put(route('admin.settings.update'), [])->assertForbidden();
        }
    }

    public function test_super_admin_can_access_all_menus_and_admin_can_manage_allowed_modules(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        foreach (['dashboard', 'admin-users.index', 'roles.index', 'settings.index', 'audit-logs.index', 'events.index'] as $route) {
            $this->actingAs($superAdmin)->get(route('admin.'.$route))->assertOk();
        }

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin)->post(route('admin.family-altars.store'), [])->assertSessionHasErrors();
        foreach (['congregations', 'family-altars', 'events', 'discipleship', 'announcements', 'pastor-messages'] as $module) {
            $this->get(route('admin.'.$module.'.create'))->assertOk();
        }
    }

    public function test_reserved_permissions_cannot_be_assigned_using_the_role_editor(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');
        $role = Role::findByName('Admin');

        $this->actingAs($superAdmin)->get(route('admin.roles.edit', $role))
            ->assertOk()->assertDontSee('value="settings.view"', false)->assertSee('value="dashboard.view"', false);
        $this->put(route('admin.roles.update', $role), ['permissions' => ['settings.view']])
            ->assertSessionHasErrors('permissions.0');
        $this->assertFalse($role->fresh()->hasPermissionTo('settings.view'));

        $this->put(route('admin.roles.update', $role), ['permissions' => ['dashboard.view', 'congregations.view']])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.roles.index'));
        $this->assertTrue($role->fresh()->hasPermissionTo('dashboard.view'));
    }

    public function test_seeding_replaces_stale_admin_permissions_and_migration_updates_existing_roles(): void
    {
        $role = Role::findByName('Admin');
        $role->syncPermissions(['dashboard.view', 'settings.update']);
        $this->seed(RolePermissionSeeder::class);
        $this->assertTrue($role->fresh()->hasPermissionTo('events.create'));
        $this->assertTrue($role->fresh()->hasPermissionTo('dashboard.view'));
        $this->assertFalse($role->fresh()->hasPermissionTo('settings.update'));

        $role->syncPermissions(['dashboard.view']);
        $migration = require database_path('migrations/2026_10_05_000000_update_admin_role_permissions.php');
        $migration->up();
        $this->assertTrue($role->fresh()->hasPermissionTo('congregations.view'));
        $this->assertTrue($role->fresh()->hasPermissionTo('dashboard.view'));
    }

    public function test_dashboard_migration_grants_existing_admins_access_and_preserves_other_permissions(): void
    {
        $role = Role::findByName('Admin');
        $role->syncPermissions(['congregations.view', 'events.view']);
        $admin = User::factory()->create();
        $admin->assignRole($role);
        $this->assertFalse($admin->can('dashboard.view'));

        $migration = require database_path('migrations/2026_11_20_000000_grant_dashboard_access_to_admin_role.php');
        $migration->up();
        $migration->up();

        $this->assertEqualsCanonicalizing(['congregations.view', 'events.view', 'dashboard.view'], $role->fresh()->permissions->pluck('name')->all());
        $this->actingAs($admin->fresh())->get(route('admin.dashboard'))->assertOk();
        $this->assertFalse(Role::findByName('Pastor')->hasPermissionTo('dashboard.view'));
    }

    public function test_admin_without_dashboard_permission_uses_an_allowed_home_page(): void
    {
        Role::findByName('Admin')->revokePermissionTo('dashboard.view');
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('admin.congregations.index'))->assertOk()->assertDontSee('href="'.route('admin.dashboard').'"', false);
        $this->get('/admin')->assertRedirect(route('admin.congregations.index'));
    }
}
