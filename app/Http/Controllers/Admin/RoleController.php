<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleController extends Controller
{
    public function index(): View
    {
        return view('admin.roles.index', ['roles' => Role::query()->withCount(['permissions', 'users'])->get()]);
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', ['role' => $role, 'permissions' => Permission::query()->orderBy('name')->get()->groupBy(fn (Permission $permission): string => str($permission->name)->before('.')->toString())]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'Super Admin', 422, 'Permission Super Admin tidak dapat dibatasi.');
        $validated = $request->validate(['permissions' => ['nullable', 'array'], 'permissions.*' => ['exists:permissions,name']]);
        $role->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        activity('roles')->causedBy($request->user())->performedOn($role)->event('updated')->log('Permission role diperbarui');

        return redirect()->route('admin.roles.index')->with('success', 'Permission role berhasil diperbarui.');
    }
}
