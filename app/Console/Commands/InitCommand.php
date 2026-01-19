<?php

namespace App\Console\Commands;

use Exception;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class InitCommand extends Command
{
    protected $signature = 'init {--fresh : Drop all tables and re-run migrations}';

    protected $description = 'Initialize the application with migrations, Shield setup, and super admin';

    public function handle(): int
    {
        $this->displayHeader();

        if (! $this->checkAndBuildAssets()) {
            return Command::FAILURE;
        }

        if (! $this->runMigrations()) {
            return Command::FAILURE;
        }

        $this->generateShieldPermissions();
        $this->setupSuperAdmin();
        $this->clearCaches();
        $this->displaySummary();

        return Command::SUCCESS;
    }

    protected function displayHeader(): void
    {
        $this->info('🚀 Starting application initialization...');
        $this->newLine();
    }

    protected function checkAndBuildAssets(): bool
    {
        $this->info('📋 Checking frontend dependencies...');

        if (! file_exists(base_path('node_modules'))) {
            $this->warn('⚠️  Node modules not found.');
            $this->line('Please run: npm install && npm run build');
            $this->newLine();

            if (! $this->confirm('Continue without building assets?', true)) {
                return false;
            }

            $this->info('⏭️  Skipping asset build');
        } else {
            $this->info('✅ Node modules found');

            if ($this->confirm('Build frontend assets?', false)) {
                $this->buildAssets();
            }
        }

        $this->newLine();

        return true;
    }

    protected function buildAssets(): void
    {
        $this->info('Building assets...');
        exec('npm run build', $output, $returnCode);

        if ($returnCode === 0) {
            $this->info('✅ Assets built successfully');
        } else {
            $this->warn('⚠️  Asset build failed. Run: npm run build');
        }
    }

    protected function runMigrations(): bool
    {
        $this->info('📦 Running migrations...');

        if ($this->option('fresh')) {
            if (! $this->confirm('⚠️  This will drop all tables. Continue?', false)) {
                $this->warn('❌ Cancelled');

                return false;
            }

            Artisan::call('migrate:fresh', [], $this->getOutput());
            $this->info('✅ Fresh migrations completed');
        } else {
            Artisan::call('migrate', [], $this->getOutput());
            $this->info('✅ Migrations completed');
        }

        $this->newLine();

        return true;
    }

    protected function generateShieldPermissions(): void
    {
        $this->info('🛡️  Generating permissions and policies...');

        try {
            Artisan::call('shield:generate', [
                '--all' => true,
                '--panel' => 'admin',
            ], $this->getOutput());
            $this->info('✅ Permissions generated');
        } catch (Exception $e) {
            $this->error('⚠️  Error: '.$e->getMessage());
        }

        $this->newLine();
    }

    protected function setupSuperAdmin(): void
    {
        $this->info('👤 Setting up super admin...');

        if ($this->confirm('Create/assign super admin?', true)) {
            $this->createOrAssignSuperAdmin();
        } else {
            $this->warn('⏭️  Skipped');
        }

        $this->newLine();
    }

    protected function clearCaches(): void
    {
        $this->info('🧹 Clearing caches...');

        Artisan::call('cache:clear', [], $this->getOutput());
        Artisan::call('config:clear', [], $this->getOutput());
        Artisan::call('route:clear', [], $this->getOutput());
        Artisan::call('view:clear', [], $this->getOutput());

        $this->info('✅ Caches cleared');
        $this->newLine();
    }

    protected function displaySummary(): void
    {
        $this->info('✨ Initialization completed!');
        $this->newLine();

        $this->line('📋 Summary:');
        $this->line('  • Database migrations: ✅');
        $this->line('  • Shield permissions: ✅');
        $this->line('  • Super admin: ✅');
        $this->line('  • Caches cleared: ✅');
        $this->newLine();

        if ($this->shouldShowAssetWarning()) {
            $this->displayAssetWarning();
        }

        $this->info('🎉 Access your application at: '.url('/admin'));
    }

    protected function shouldShowAssetWarning(): bool
    {
        return ! file_exists(base_path('node_modules'))
            || ! file_exists(public_path('build/manifest.json'));
    }

    protected function displayAssetWarning(): void
    {
        $this->warn('⚠️  Frontend assets not built!');
        $this->line('   Run: npm install && npm run build');
        $this->newLine();
    }

    protected function createOrAssignSuperAdmin(): void
    {
        $email = $this->ask('Email address');

        if (! $email) {
            $this->error('Email is required!');

            return;
        }

        $user = $this->findOrCreateUser($email);
        $this->assignSuperAdminRole($user);
        $this->displayUserInfo($user);
    }

    protected function findOrCreateUser(string $email): User
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->info('Creating new user...');
            $user = $this->createNewUser($email);
            $this->info('✅ User created');
        } else {
            $this->info('User found. Assigning role...');
        }

        return $user;
    }

    protected function createNewUser(string $email): User
    {
        $name = $this->ask('Name', 'Admin');
        $password = $this->secret('Password (min 8 characters)');

        if (strlen((string) $password) < 8) {
            $this->error('Password must be at least 8 characters!');
            exit(1);
        }

        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
        ]);
    }

    protected function assignSuperAdminRole(User $user): void
    {
        Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );

        if (! $user->hasRole('super_admin')) {
            $user->assignRole('super_admin');
            $this->info('✅ Super admin role assigned');
        } else {
            $this->info('✅ Already has super admin role');
        }
    }

    protected function displayUserInfo(User $user): void
    {
        $this->newLine();
        $this->line('📧 Email: '.$user->email);
        $this->line('👤 Name: '.$user->name);
        $this->line('🔑 Role: super_admin');
    }
}
