<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\RelationManagers\ActivitiesRelationManager;
use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * The audit trail is readable and read-only, and the diff view renders the
 * change it is meant to show.
 */
class ActivityResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_activity_list_opens_for_a_super_admin(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(ActivityResource::getUrl('index'))
            ->assertOk();
    }

    public function test_the_activity_list_is_refused_without_permission(): void
    {
        $this->actingAs($this->panelUser())
            ->get(ActivityResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_the_view_page_shows_the_diff(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin);

        $subject = User::factory()->create(['name' => 'Before']);
        $subject->update(['name' => 'After']);

        $activity = Activity::query()->where('event', 'updated')->latest('id')->firstOrFail();

        $this->get(ActivityResource::getUrl('view', ['record' => $activity]))
            ->assertOk()
            ->assertSee('Before')
            ->assertSee('After');
    }

    public function test_activity_records_cannot_be_created(): void
    {
        $this->assertFalse(ActivityResource::canCreate());
    }

    public function test_the_relation_manager_lists_a_users_own_history(): void
    {
        $this->actingAs($this->superAdmin());

        $subject = User::factory()->create(['name' => 'Before']);
        $subject->update(['name' => 'After']);

        Livewire::test(ActivitiesRelationManager::class, [
            'ownerRecord' => $subject->fresh(),
            'pageClass' => EditUser::class,
        ])
            ->assertOk()
            ->assertSee(__('activity.events.updated'));
    }
}
