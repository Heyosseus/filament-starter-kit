<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * The audit trail records what changed, attributes it, and never writes the
 * password hash into a log anyone can read.
 *
 * Note the diff lives in `attribute_changes`, not `properties` — the two were
 * one column before activitylog 5.
 */
class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_user_is_recorded(): void
    {
        User::factory()->create();

        $activity = Activity::query()->latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame('created', $activity->event);
        $this->assertSame(User::class, $activity->subject_type);
        $this->assertSame('user', $activity->log_name);
    }

    public function test_updating_a_user_records_the_changed_field(): void
    {
        $user = User::factory()->create(['name' => 'Original']);

        $user->update(['name' => 'Renamed']);

        $activity = Activity::query()->where('event', 'updated')->latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame('Renamed', $activity->attribute_changes['attributes']['name']);
        $this->assertSame('Original', $activity->attribute_changes['old']['name']);
    }

    public function test_only_the_dirty_field_is_recorded(): void
    {
        $user = User::factory()->create(['name' => 'Original']);

        $user->update(['name' => 'Renamed']);

        $activity = Activity::query()->where('event', 'updated')->latest('id')->first();

        $this->assertSame(['name'], array_keys($activity->attribute_changes['attributes']));
    }

    public function test_the_password_hash_is_never_logged(): void
    {
        $user = User::factory()->create();

        $user->update(['password' => 'a-brand-new-password']);

        $logged = Activity::query()->get()
            ->flatMap(fn (Activity $activity): array => array_merge(
                array_keys($activity->attribute_changes['attributes'] ?? []),
                array_keys($activity->attribute_changes['old'] ?? []),
            ))
            ->unique()
            ->all();

        $this->assertNotEmpty(
            Activity::query()->where('event', 'created')->first()?->attribute_changes,
            'Guard against this passing vacuously: something must actually be logged.',
        );
        $this->assertNotContains('password', $logged);
        $this->assertNotContains('remember_token', $logged);
    }

    public function test_a_change_with_nothing_dirty_is_not_logged(): void
    {
        $user = User::factory()->create();
        $before = Activity::query()->count();

        $user->update(['name' => $user->name]);

        $this->assertSame($before, Activity::query()->count());
    }

    public function test_the_signed_in_user_is_recorded_as_the_causer(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin);

        $subject = User::factory()->create();
        $subject->update(['name' => 'Changed By Admin']);

        $activity = Activity::query()->where('event', 'updated')->latest('id')->first();

        $this->assertNotNull($activity);
        $this->assertSame($admin->getKey(), $activity->causer_id);
    }
}
