<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * `init` has to work where there is no terminal to prompt at — CI, Docker,
 * Git Bash, an IDE console. Supplying credentials up front must skip every
 * prompt, because prompting against a pipe either loops forever (Windows,
 * which always uses Symfony's re-asking fallback) or throws
 * NonInteractiveValidationException (everywhere else).
 */
class InitCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * `init` runs shield:generate, which rewrites the policy classes on disk —
     * and does so unformatted, so a test run would otherwise leave the working
     * tree dirty. Redirecting the output is not an option because Shield
     * derives the policy namespace from the path via PSR-4, so the files are
     * snapshotted and put back instead.
     *
     * @var array<string, string>
     */
    private array $policySnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->policySnapshot = collect(glob(app_path('Policies/*.php')) ?: [])
            ->mapWithKeys(fn (string $file): array => [$file => (string) file_get_contents($file)])
            ->all();
    }

    protected function tearDown(): void
    {
        foreach (glob(app_path('Policies/*.php')) ?: [] as $file) {
            if (! array_key_exists($file, $this->policySnapshot)) {
                unlink($file);
            }
        }

        foreach ($this->policySnapshot as $file => $contents) {
            file_put_contents($file, $contents);
        }

        parent::tearDown();
    }

    public function test_options_create_a_super_admin_without_prompting(): void
    {
        $this->artisan('init', [
            '--admin-email' => 'founder@example.test',
            '--admin-password' => 'a-strong-password',
            '--admin-name' => 'Founder',
        ])->assertSuccessful();

        $user = User::query()->where('email', 'founder@example.test')->firstOrFail();

        $this->assertSame('Founder', $user->name);
        $this->assertTrue($user->hasRole(Utils::getSuperAdminName()));
        $this->assertTrue(Hash::check('a-strong-password', $user->password));
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_an_existing_user_is_promoted_rather_than_duplicated(): void
    {
        $existing = User::factory()->create([
            'email' => 'existing@example.test',
            'password' => 'their-own-password',
        ]);

        $this->artisan('init', [
            '--admin-email' => 'existing@example.test',
            '--admin-password' => 'ignored-because-they-exist',
        ])->assertSuccessful();

        $existing->refresh();

        $this->assertSame(1, User::query()->where('email', 'existing@example.test')->count());
        $this->assertTrue($existing->hasRole(Utils::getSuperAdminName()));
        $this->assertTrue(
            Hash::check('their-own-password', $existing->password),
            'Promoting an existing account must not change their password.',
        );
    }

    public function test_credentials_are_read_from_the_environment(): void
    {
        putenv('ADMIN_EMAIL=from-env@example.test');
        putenv('ADMIN_PASSWORD=env-supplied-password');

        try {
            $this->artisan('init')->assertSuccessful();
        } finally {
            putenv('ADMIN_EMAIL');
            putenv('ADMIN_PASSWORD');
        }

        $this->assertDatabaseHas('users', ['email' => 'from-env@example.test']);
    }

    public function test_skip_admin_creates_nobody(): void
    {
        $this->artisan('init', ['--skip-admin' => true])->assertSuccessful();

        $this->assertSame(0, User::query()->count());
    }

    public function test_a_new_account_is_refused_without_a_usable_password(): void
    {
        $this->artisan('init', [
            '--admin-email' => 'no-password@example.test',
            '--admin-password' => 'short',
        ])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'no-password@example.test']);
    }

    public function test_it_still_runs_migrations_and_permissions(): void
    {
        $this->artisan('init', ['--skip-admin' => true])->assertSuccessful();

        $this->assertDatabaseCount('permissions', 41);
    }
}
