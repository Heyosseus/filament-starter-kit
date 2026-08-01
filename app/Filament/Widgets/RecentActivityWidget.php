<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Activities\Tables\ActivitiesTable;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * The last handful of audited changes, so the dashboard answers "what happened
 * while I was away" without a trip to the full log.
 */
class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    private const int LIMIT = 8;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('dashboard.widgets.recent_activity.heading'))
            ->description(__('dashboard.widgets.recent_activity.description'))
            ->query(
                Activity::query()
                    ->with('causer')
                    ->latest('id')
                    ->limit(self::LIMIT)
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('activity.fields.when'))
                    ->since()
                    ->tooltip(fn (Activity $record): ?string => $record->created_at?->toDateTimeString()),
                TextColumn::make('event')
                    ->label(__('activity.fields.event'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => ActivitiesTable::describe($state)),
                TextColumn::make('subject_type')
                    ->label(__('activity.fields.subject'))
                    ->formatStateUsing(fn (?string $state, Activity $record): string => $state === null
                        ? '—'
                        : Str::headline(class_basename($state)).($record->subject_id !== null ? " #{$record->subject_id}" : '')),
                TextColumn::make('causer.name')
                    ->label(__('activity.fields.by'))
                    ->placeholder(__('activity.system')),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('activity.relation.open'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Activity $record): string => ActivityResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading(__('dashboard.widgets.recent_activity.empty'));
    }
}
