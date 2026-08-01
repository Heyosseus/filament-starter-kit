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
        {--skip-admin : Do not create a super admin}
        {--admin-name= : Super admin name, instead of being asked for it}
        {--admin-email= : Super admin email, instead of being asked for it}
        {--admin-password= : Super admin password, instead of being asked for it}';

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
     * `--fresh` drops every table, so it asks first — unless there is nobody to
     * ask, in which case passing the flag is itself the confirmation.
     */
    private function confirmDestructive(): bool
    {
        if (! $this->canPrompt()) {
            return true;
        }

        return confirm(
            label: 'This drops every table in '.config('database.connections.'.config('database.default').'.database').'. Continue?',
            default: false,
        );
    }

    /**
     * Whether there is a human on the other end who can answer a prompt.
     *
     * This mirrors how Laravel decides to put Laravel Prompts into interactive
     * mode. Symfony's own `isInteractive()` is not enough on its own: it stays
     * true whenever `--no-interaction` was not passed, including when stdin is
     * a pipe rather than a terminal — which is the case under Git Bash/MinTTY,
     * most IDE consoles, CI, and `docker run` without `-t`.
     *
     * Prompting anyway breaks in two different ways depending on the platform.
     * On Windows every prompt uses Symfony's fallback, which re-asks until it
     * gets a valid answer and so loops forever against a pipe. Everywhere else
     * the prompt goes non-interactive, validates its empty default against
     * `required`, and throws NonInteractiveValidationException.
     */
    private function canPrompt(): bool
    {
        if ($this->laravel->runningUnitTests()) {
            return true;
        }

        return $this->input->isInteractive()
            && defined('STDIN')
            && stream_isatty(STDIN);
    }

    private function generatePermissions(): bool
    {
        $exitCode = null;

        $this->components->task('Generating policies and permissions', function () use (&$exitCode): bool {
            /*
             * `--option` is passed explicitly because shield:generate asks
             * what to generate when it is missing, and this command must not
             * depend on someone being there to answer.
             */
            $exitCode = Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => 'admin',
                '--option' => 'policies_and_permissions',
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

    /**
     * Credentials come from options first, then the environment, and only then
     * from a prompt — so this works the same in a terminal, a CI job and a
     * Dockerfile.
     */
    private function setUpSuperAdmin(): void
    {
        $email = $this->credential('admin-email', 'ADMIN_EMAIL');
        $password = $this->credential('admin-password', 'ADMIN_PASSWORD');

        if (filled($email) && filled($password)) {
            $this->createOrPromote($email, $password, $this->credential('admin-name', 'ADMIN_NAME') ?? 'Admin');

            return;
        }

        if (! $this->canPrompt()) {
            $this->components->warn('Skipping super admin — nothing to prompt with.');
            $this->components->bulletList([
                'php artisan init --admin-email=you@example.com --admin-password=secret',
                'or set ADMIN_EMAIL and ADMIN_PASSWORD in .env',
                'or run php artisan shield:super-admin --user=1 later',
            ]);

            return;
        }

        if (! confirm(label: 'Create or promote a super admin now?', default: true)) {
            $this->components->warn('Skipped. Run `php artisan shield:super-admin` when you are ready.');

            return;
        }

        $email ??= text(
            label: 'Email address',
            required: true,
            validate: fn (string $value): ?string => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                ? 'Enter a valid email address.'
                : null,
        );

        if (User::query()->where('email', $email)->exists()) {
            $this->createOrPromote($email, null, null);

            return;
        }

        $name = $this->credential('admin-name', 'ADMIN_NAME')
            ?? text(label: 'Name', default: 'Admin', required: true);

        $password ??= password(
            label: 'Password',
            required: true,
            validate: fn (string $value): ?string => strlen($value) < 8
                ? 'Password must be at least 8 characters.'
                : null,
        );

        $this->createOrPromote($email, $password, $name);
    }

    private function credential(string $option, string $env): ?string
    {
        $value = $this->option($option) ?? env($env);

        return filled($value) ? (string) $value : null;
    }

    /**
     * An existing account is promoted rather than duplicated, and keeps the
     * password it already has.
     */
    private function createOrPromote(string $email, ?string $password, ?string $name): void
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            if ($password === null || strlen($password) < 8) {
                $this->components->error('A password of at least 8 characters is required to create '.$email.'.');

                return;
            }

            $user = User::create([
                'name' => $name ?? 'Admin',
                'email' => $email,
                'password' => $password,
                'email_verified_at' => now(),
            ]);

            $this->components->info("Created {$user->email}.");
        } else {
            $this->components->info("Found {$user->email}.");
        }

        $this->promote($user);
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
