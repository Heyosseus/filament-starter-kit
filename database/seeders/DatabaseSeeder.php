<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds a super admin for local development.
 *
 * Credentials come from the environment and there is no built-in fallback
 * password — a starter kit that ships a known admin login is a starter kit that
 * gets deployed with one. Set ADMIN_EMAIL and ADMIN_PASSWORD, or use
 * `php artisan init`, which prompts for them.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (blank($email) || blank($password)) {
            $this->command?->warn('Skipping the super admin — set ADMIN_EMAIL and ADMIN_PASSWORD, or run `php artisan init`.');

            return;
        }

        $role = Role::findOrCreate(Utils::getSuperAdminName(), Utils::getFilamentAuthGuard());
        $role->syncPermissions(Permission::all());

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Admin'),
                'password' => $password,
                'email_verified_at' => now(),
            ],
        );

        $user->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->command?->info("Super admin ready: {$user->email} ({$role->permissions()->count()} permissions).");
    }
}
