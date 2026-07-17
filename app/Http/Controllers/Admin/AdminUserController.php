<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

final class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()->with('roles')->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->search.'%')->orWhere('email', 'like', '%'.$request->search.'%')))->latest()->paginate(15)->withQueryString();

        return view('admin.resources.index', ['title' => 'Admin Users', 'routeBase' => 'admin.admin-users', 'createPermission' => 'admins.create', 'items' => $users, 'columns' => ['name' => 'Nama', 'email' => 'Email', 'role' => 'Role', 'active' => 'Status'], 'rows' => $users->through(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->getRoleNames()->join(', '), 'active' => $user->is_active ? 'Aktif' : 'Nonaktif'])]);
    }

    public function create(): View
    {
        return $this->form(new User, 'Tambah Admin');
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::query()->create(array_merge($request->safe()->except(['role', 'password_confirmation']), ['is_active' => $request->boolean('is_active')]));
            $user->assignRole($request->string('role')->toString());

            return $user;
        });
        activity('admins')->causedBy($request->user())->performedOn($user)->event('created')->log('Admin dibuat');

        return redirect()->route('admin.admin-users.index')->with('success', 'Admin berhasil ditambahkan.');
    }

    public function show(User $adminUser): View
    {
        return view('admin.resources.show', ['title' => 'Detail Admin', 'routeBase' => 'admin.admin-users', 'item' => $adminUser, 'details' => ['Nama' => $adminUser->name, 'Email' => $adminUser->email, 'Role' => $adminUser->getRoleNames()->join(', '), 'Status' => $adminUser->is_active ? 'Aktif' : 'Nonaktif', 'Login terakhir' => $adminUser->last_login_at?->format('d M Y H:i') ?? '-']]);
    }

    public function edit(User $adminUser): View
    {
        return $this->form($adminUser, 'Edit Admin');
    }

    public function update(UpdateAdminUserRequest $request, User $adminUser): RedirectResponse
    {
        abort_if($adminUser->hasRole('Super Admin') && ! $request->user()->hasRole('Super Admin'), 403);
        abort_if($request->user()->is($adminUser) && ! $request->boolean('is_active'), 422, 'Admin tidak dapat menonaktifkan akun sendiri.');
        abort_if($adminUser->hasRole('Super Admin') && $request->string('role')->toString() !== 'Super Admin' && User::role('Super Admin')->count() <= 1, 422, 'Super Admin terakhir tidak dapat diturunkan rolenya.');
        DB::transaction(function () use ($request, $adminUser): void {
            $data = $request->safe()->except(['role', 'password_confirmation']);
            if (empty($data['password'])) {
                unset($data['password']);
            } $data['is_active'] = $request->boolean('is_active');
            $adminUser->update($data);
            $adminUser->syncRoles($request->string('role')->toString());
        });

        return redirect()->route('admin.admin-users.index')->with('success', 'Admin berhasil diperbarui.');
    }

    public function destroy(Request $request, User $adminUser): RedirectResponse
    {
        abort_unless($request->user()->can('admins.delete'), 403);
        abort_if($request->user()->is($adminUser), 422, 'Admin tidak dapat menghapus akun sendiri.');
        if ($adminUser->hasRole('Super Admin')) {
            abort_if(User::role('Super Admin')->count() <= 1, 422, 'Super Admin terakhir tidak dapat dihapus.');
        }
        $adminUser->delete();

        return back()->with('success', 'Admin berhasil dihapus.');
    }

    private function form(User $item, string $title): View
    {
        return view('admin.resources.form', ['title' => $title, 'routeBase' => 'admin.admin-users', 'item' => $item, 'fields' => [['name' => 'name', 'label' => 'Nama', 'required' => true], ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true], ['name' => 'role', 'label' => 'Role', 'type' => 'select', 'options' => Role::query()->pluck('name', 'name'), 'value' => $item->getRoleNames()->first()], ['name' => 'password', 'label' => 'Password', 'type' => 'password'], ['name' => 'password_confirmation', 'label' => 'Konfirmasi Password', 'type' => 'password'], ['name' => 'is_active', 'label' => 'Admin aktif', 'type' => 'checkbox', 'value' => $item->exists ? $item->is_active : true]]]);
    }
}
