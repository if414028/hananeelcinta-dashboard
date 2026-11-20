<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findOrCreate('Admin', 'web')->givePermissionTo(Permission::findOrCreate('dashboard.view', 'web'));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::query()->where('name', 'Admin')->where('guard_name', 'web')->first()?->revokePermissionTo('dashboard.view');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
