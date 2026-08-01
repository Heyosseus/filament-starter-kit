<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\RecentActivityWidget;
use App\Filament\Widgets\UserRegistrationsChartWidget;
use App\Filament\Widgets\UserStatsWidget;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Widget order is fixed here rather than left to discovery, so the page always
 * reads top-down: the totals, then the trend behind them, then what changed
 * most recently.
 */
class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = -1;

    public function getTitle(): string|Htmlable
    {
        return __('dashboard.title');
    }

    public static function getNavigationLabel(): string
    {
        return __('dashboard.title');
    }

    public function getWidgets(): array
    {
        return [
            UserStatsWidget::class,
            UserRegistrationsChartWidget::class,
            RecentActivityWidget::class,
        ];
    }

    /**
     * @return int|array<string, ?int>
     */
    public function getColumns(): int|array
    {
        return 2;
    }
}
