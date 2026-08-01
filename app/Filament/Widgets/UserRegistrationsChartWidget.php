<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

/**
 * Sign-ups per day over the trailing month. Days with no registrations are
 * filled in as zero so the line reflects real gaps rather than skipping them.
 */
class UserRegistrationsChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $maxHeight = '280px';

    protected int|string|array $columnSpan = 1;

    private const int DAYS = 30;

    public function getHeading(): string|Htmlable|null
    {
        return __('dashboard.widgets.registrations.heading');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('dashboard.widgets.registrations.description', ['days' => self::DAYS]);
    }

    protected function getData(): array
    {
        $start = Carbon::today()->subDays(self::DAYS - 1);

        $counts = User::query()
            ->where('created_at', '>=', $start)
            ->get(['created_at'])
            ->countBy(fn (User $user): string => $user->created_at->toDateString());

        $labels = [];
        $points = [];

        for ($day = $start->copy(); $day->lte(Carbon::today()); $day->addDay()) {
            $labels[] = $day->format('M j');
            $points[] = $counts->get($day->toDateString(), 0);
        }

        return [
            'datasets' => [
                [
                    'label' => __('dashboard.widgets.registrations.label'),
                    'data' => $points,
                    'borderColor' => 'rgb(16, 185, 129)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
            ],
        ];
    }
}
