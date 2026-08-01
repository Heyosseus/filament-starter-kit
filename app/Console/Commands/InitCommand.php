<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * One command to take a freshly cloned checkout to a working panel: schema,
 * permissions, and an account that can sign in.
 *
 * Safe to re-run — migrations are additive unless --fresh is passed, permission
 * generation is idempotent, and an existing user is promoted rather than
 * duplicated.
 */
class InitCommand extends Command
{
    protected $signature = 'init
        {--fresh : Drop all tables and re-run migrations}
        {--skip-admin : Do not prompt to create a super admin}';

    protected $description = 'Initialise the application: migrations, Shield permissions, and a super admin';

    public function handle(): int
    {
        $this->components->info('Initialising '.config('app.name'));

        if (! $this->runMigrations()) {
            return self::FAILURE;
        }

        if (! $this->generatePermissions()) {
            return self::FAILURE;
        }

        if (! $this->option('skip-admin')) {
            $this->setUpSuperAdmin();
        }

        $this->clearCaches();
        $this->summarise();

        return self::SUCCESS;
    }

    private function runMigrations(): bool
    {
        if ($this->option('fresh')) {
            if (! $this->confirmDestructive()) {
                $this->components->warn('Cancelled — no changes made.');

                return false;
            }

            $this->components->task(
                'Dropping tables and re-running migrations',
                fn (): bool => Artisan::call('migrate:fresh', ['--force' => true]) === 0,
            );

            return true;
        }

        $this->components->task(
            'Running migrations',
            fn (): bool => Artisan::call('migrate', ['--force' => true]) === 0,
        );

        return true;
    }

    /**
     * `--fresh` drops every table, so it asks first — unless the caller has
     * already opted out of interaction, in which case they have said as much.
     */
    private function confirmDestructive(): bool
    {
        if (! $this->input->isInteractive()) {
            return true;
        }

        return confirm(
            label: 'This drops every table in '.config('database.connections.'.config('database.default').'.database').'. Continue?',
            default: false,
        );
    }

    private function generatePermissions(): bool
    {
        $exitCode = null;

        $this->components->task('Generating policies and permissions', function () use (&$exitCode): bool {
            $exitCode = Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => 'admin',
                '--no-interaction' => true,
            ]);

            return $exitCode === 0;
        });

        if ($exitCode !== 0) {
            $this->components->error('Shield could not generate permissions. Run `php artisan shield:generate --all --panel=admin` to see why.');

            return false;
        }

        return true;
    }

    private function setUpSuperAdmin(): void
    {
        if (! $this->input->isInteractive()) {
            $this->components->warn('Skipping super admin — not running interactively. Use `php artisan shield:super-admin` later.');

            return;
        }

        if (! confirm(label: 'Create or promote a super admin now?', default: true)) {
            $this->components->warn('Skipped. Run `php artisan shield:super-admin` when you are ready.');

            return;
        }

        $email = text(
            label: 'Email address',
            required: true,
            validate: fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                ? 'Enter a valid email address.'
                : null,
        );

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $user = $this->createUser($email);
            $this->components->info("Created {$user->email}.");
        } else {
            $this->components->info("Found {$user->email}.");
        }

        $this->promote($user);
    }

    private function createUser(string $email): User
    {
        $name = text(label: 'Name', default: 'Admin', required: true);

        $password = password(
            label: 'Password',
            required: true,
            validate: fn (string $value): ?string => strlen($value) < 8
                ? 'Password must be at least 8 characters.'
                : null,
        );

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'email_verified_at' => now(),
        ]);
    }

    private function promote(User $user): void
    {
        $roleName = Utils::getSuperAdminName();

        Role::findOrCreate($roleName, Utils::getFilamentAuthGuard());

        if ($user->hasRole($roleName)) {
            $this->components->info("{$user->email} already holds the {$roleName} role.");

            return;
        }

        $user->assignRole($roleName);
        $this->components->info("Assigned {$roleName} to {$user->email}.");
    }

    private function clearCaches(): void
    {
        $this->components->task('Clearing caches', function (): bool {
            /*
             * event:clear matters as much as the rest: a cached event map
             * predates any listener added since, and a stale one silently
             * stops listeners firing rather than failing loudly.
             */
            foreach (['cache:clear', 'config:clear', 'route:clear', 'view:clear', 'event:clear'] as $command) {
                Artisan::call($command);
            }

            return true;
        });
    }

    private function summarise(): void
    {
        $this->newLine();

        if (! file_exists(public_path('build/manifest.json'))) {
            $this->components->warn('Frontend assets are not built yet — run `npm install && npm run build`.');
        }

        $this->components->info('Ready. The panel is at '.url('/admin').'.');
    }
}
