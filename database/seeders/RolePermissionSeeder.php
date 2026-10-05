<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\AdminAccess;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    private const PERMISSIONS = [
        'dashboard.view',
        'admins.view', 'admins.create', 'admins.update', 'admins.delete',
        'congregations.view', 'congregations.create', 'congregations.update', 'congregations.delete', 'congregations.export',
        'announcements.view', 'announcements.create', 'announcements.update', 'announcements.delete', 'announcements.publish',
        'prayer_requests.view', 'prayer_requests.view_confidential', 'prayer_requests.update', 'prayer_requests.delete', 'prayer_requests.export',
        'family_altars.view', 'family_altars.create', 'family_altars.update', 'family_altars.delete',
        'pastor_messages.view', 'pastor_messages.create', 'pastor_messages.update', 'pastor_messages.delete', 'pastor_messages.publish',
        'events.view', 'events.create', 'events.update', 'events.delete', 'events.registrations', 'events.check_in',
        'settings.view', 'settings.update', 'audit_logs.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('Super Admin', 'web')->syncPermissions(Permission::all());
        Role::findOrCreate('Admin', 'web')->syncPermissions(AdminAccess::ADMIN_PERMISSIONS);
        Role::findOrCreate('Pastor', 'web')->givePermissionTo(['prayer_requests.view', 'prayer_requests.view_confidential', 'prayer_requests.update']);
    }
}
