<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $name = env('SUPER_ADMIN_NAME', 'Super Admin');
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (! is_string($email) || $email === '' || ! is_string($password) || $password === '') {
            if (app()->environment('production')) {
                throw new RuntimeException('SUPER_ADMIN_EMAIL dan SUPER_ADMIN_PASSWORD wajib diatur di production.');
            }

            $this->command?->warn('Super Admin dilewati: atur SUPER_ADMIN_EMAIL dan SUPER_ADMIN_PASSWORD.');

            return;
        }

        $user = User::withTrashed()->updateOrCreate(
            ['email' => mb_strtolower($email)],
            ['name' => $name, 'password' => Hash::make($password), 'is_active' => true, 'deleted_at' => null],
        );

        $user->syncRoles('Super Admin');
    }
}
