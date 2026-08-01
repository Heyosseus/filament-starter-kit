<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\UserRegistrationsChartWidget;
use App\Filament\Widgets\UserStatsWidget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The dashboard page only renders widget placeholders — Livewire loads each
 * widget separately — so the widgets are mounted directly here. Without this
 * the widget code never runs in the suite at all.
 */
class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->superAdmin());
    }

    public function test_the_stats_widget_renders(): void
    {
        User::factory()->count(3)->create();
        User::factory()->unverified()->create();

        Livewire::test(UserStatsWidget::class)
            ->assertOk()
            ->assertSee(__('dashboard.stats.users'))
            ->assertSee(__('dashboard.stats.roles'))
            ->assertSee(__('dashboard.stats.active_sessions'));
    }

    /**
     * The sessions stat queries a table that only exists on the database
     * session driver. Tests run on the array driver, so this covers the branch
     * that has to notice that and not blow up.
     */
    public function test_the_stats_widget_reports_sessions_as_unavailable_off_the_database_driver(): void
    {
        config()->set('session.driver', 'array');

        Livewire::test(UserStatsWidget::class)
            ->assertOk()
            ->assertSee(__('dashboard.stats.sessions_unavailable'));
    }

    /**
     * The counting query itself, against real rows: one stale session, and two
     * live ones belonging to the same person, who should count once.
     */
    public function test_the_sessions_stat_counts_distinct_recently_active_users(): void
    {
        config()->set('session.driver', 'database');
        config()->set('session.lifetime', 120);

        $active = User::factory()->create();
        $stale = User::factory()->create();

        DB::table('sessions')->insert([
            [
                'id' => 'live-one',
                'user_id' => $active->getKey(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => '',
                'last_activity' => now()->getTimestamp(),
            ],
            [
                'id' => 'live-two',
                'user_id' => $active->getKey(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => '',
                'last_activity' => now()->subMinutes(5)->getTimestamp(),
            ],
            [
                'id' => 'expired',
                'user_id' => $stale->getKey(),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => '',
                'last_activity' => now()->subHours(5)->getTimestamp(),
            ],
            [
                'id' => 'anonymous',
                'user_id' => null,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'test',
                'payload' => '',
                'last_activity' => now()->getTimestamp(),
            ],
        ]);

        Livewire::test(UserStatsWidget::class)
            ->assertOk()
            ->assertSee(__('dashboard.stats.active_sessions'))
            ->assertDontSee(__('dashboard.stats.sessions_unavailable'));

        $since = now()->subMinutes(120)->getTimestamp();

        $this->assertSame(1, DB::table('sessions')
            ->where('last_activity', '>=', $since)
            ->whereNotNull('user_id')
            ->distinct()
            ->count('user_id'));
    }

    public function test_the_registrations_chart_renders(): void
    {
        User::factory()->count(2)->create();

        Livewire::test(UserRegistrationsChartWidget::class)->assertOk();
    }

    public function test_the_recent_activity_widget_renders(): void
    {
        $user = User::factory()->create();
        $user->update(['name' => 'Renamed']);

        Livewire::test(RecentActivityWidget::class)
            ->assertOk()
            ->assertSee(__('activity.events.updated'));
    }

    public function test_the_recent_activity_widget_renders_when_nothing_has_happened(): void
    {
        Livewire::test(RecentActivityWidget::class)->assertOk();
    }
}
