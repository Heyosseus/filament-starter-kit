<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Signing in and out, and changes to a role, all land in the same audit trail
 * as changes to a user. Carried over from the hand-rolled activity log that
 * this branch replaced with spatie's.
 */
class AuthActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_listener_is_discovered(): void
    {
        $this->assertNotEmpty(
            Event::getListeners(Login::class),
            'Laravel discovers listeners in app/Listeners; nothing registered for Login.',
        );
        $this->assertNotEmpty(Event::getListeners(Logout::class));
        $this->assertNotEmpty(Event::getListeners(Failed::class));
    }

    public function test_signing_in_is_recorded(): void
    {
        $user = User::factory()->create();

        event(new Login('web', $user, false));

        $activity = Activity::query()->where('log_name', 'auth')->latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame('login', $activity->event);
        $this->assertSame($user->getKey(), $activity->causer_id);
        $this->assertArrayHasKey('ip', $activity->properties->toArray());
        $this->assertArrayHasKey('user_agent', $activity->properties->toArray());
    }

    public function test_signing_out_is_recorded(): void
    {
        $user = User::factory()->create();

        event(new Logout('web', $user));

        $activity = Activity::query()->where('event', 'logout')->latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame($user->getKey(), $activity->causer_id);
    }

    public function test_a_failed_attempt_is_recorded_without_a_causer(): void
    {
        event(new Failed('web', null, ['email' => 'nobody@example.test']));

        $activity = Activity::query()->where('event', 'login_failed')->latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertNull($activity->causer_id);
        $this->assertSame('nobody@example.test', $activity->properties['email']);
    }

    /**
     * A logout with no user (an already-expired session) must not record an
     * unattributed logout.
     */
    public function test_a_logout_without_a_user_is_ignored(): void
    {
        $before = Activity::query()->count();

        event(new Logout('web', null));

        $this->assertSame($before, Activity::query()->count());
    }

    public function test_renaming_a_role_is_recorded(): void
    {
        $role = Role::findOrCreate('editor', Utils::getFilamentAuthGuard());

        $role->update(['name' => 'senior_editor']);

        $activity = Activity::query()->where('log_name', 'role')->where('event', 'updated')->latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame('senior_editor', $activity->attribute_changes['attributes']['name']);
        $this->assertSame('editor', $activity->attribute_changes['old']['name']);
    }

    public function test_the_configured_role_model_is_ours(): void
    {
        $this->assertSame(Role::class, config('permission.models.role'));
        $this->assertInstanceOf(Role::class, Utils::getRoleModel()::query()->make());
    }
}
