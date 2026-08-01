<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\Role;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

/**
 * The three numbers worth knowing at a glance: how many people exist, how many
 * distinct roles they are sorted into, and how many are signed in right now.
 */
class UserStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            $this->usersStat(),
            $this->rolesStat(),
            $this->sessionsStat(),
        ];
    }

    private function usersStat(): Stat
    {
        $total = User::query()->count();
        $unverified = User::query()->whereNull('email_verified_at')->count();

        return Stat::make(__('dashboard.stats.users'), number_format($total))
            ->description($unverified > 0
                ? __('dashboard.stats.users_unverified', ['count' => number_format($unverified)])
                : __('dashboard.stats.users_all_verified'))
            ->descriptionIcon($unverified > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
            ->color($unverified > 0 ? 'warning' : 'success');
    }

    private function rolesStat(): Stat
    {
        $roles = Role::query()->count();

        return Stat::make(__('dashboard.stats.roles'), number_format($roles))
            ->description(__('dashboard.stats.roles_description'))
            ->descriptionIcon('heroicon-m-shield-check')
            ->color('primary');
    }

    /**
     * Only meaningful on the database session driver; on any other driver there
     * is no sessions table to count and the stat reports as unavailable.
     */
    private function sessionsStat(): Stat
    {
        if (config('session.driver') !== 'database') {
            return Stat::make(__('dashboard.stats.active_sessions'), '—')
                ->description(__('dashboard.stats.sessions_unavailable'))
                ->descriptionIcon('heroicon-m-information-circle')
                ->color('gray');
        }

        $since = now()->subMinutes((int) config('session.lifetime', 120))->getTimestamp();

        $active = DB::table(config('session.table', 'sessions'))
            ->where('last_activity', '>=', $since)
            ->whereNotNull('user_id')
            ->distinct()
            ->count('user_id');

        return Stat::make(__('dashboard.stats.active_sessions'), number_format($active))
            ->description(__('dashboard.stats.sessions_description', [
                'minutes' => (int) config('session.lifetime', 120),
            ]))
            ->descriptionIcon('heroicon-m-signal')
            ->color('info');
    }
}
