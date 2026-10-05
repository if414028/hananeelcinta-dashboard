<?php

use App\Support\AdminAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AdminAccess::ADMIN_PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findOrCreate('Admin', 'web')->syncPermissions(AdminAccess::ADMIN_PERMISSIONS);

        // Reserved permissions must not remain assigned to other roles or users.
        foreach (Permission::query()->get() as $permission) {
            if (AdminAccess::isSuperAdminPermission($permission->name)) {
                $permission->roles()->detach(Role::query()->where('name', '!=', 'Super Admin')->pluck('id'));
                $permission->users()->detach();
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep the restricted access policy when rolling back schema migrations.
    }
};
