<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities\Tables;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

class ActivitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->deferLoading()
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('causer'))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('activity.fields.when'))
                    ->since()
                    ->tooltip(fn (Activity $record): ?string => $record->created_at?->toDateTimeString())
                    ->sortable(),
                TextColumn::make('event')
                    ->label(__('activity.fields.event'))
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => self::describe($state)),
                TextColumn::make('subject_type')
                    ->label(__('activity.fields.subject'))
                    ->formatStateUsing(fn (?string $state, Activity $record): string => $state === null
                        ? '—'
                        : Str::headline(class_basename($state)).($record->subject_id !== null ? " #{$record->subject_id}" : ''))
                    ->searchable(),
                TextColumn::make('causer.name')
                    ->label(__('activity.fields.by'))
                    ->placeholder(__('activity.system'))
                    ->weight('medium')
                    ->searchable(),
                TextColumn::make('log_name')
                    ->label(__('activity.fields.area'))
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : Str::headline($state))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label(__('activity.fields.event'))
                    ->options([
                        'created' => __('activity.events.created'),
                        'updated' => __('activity.events.updated'),
                        'deleted' => __('activity.events.deleted'),
                    ]),
                SelectFilter::make('log_name')
                    ->label(__('activity.fields.area'))
                    ->options(fn (): array => Activity::query()
                        ->distinct()
                        ->whereNotNull('log_name')
                        ->pluck('log_name', 'log_name')
                        ->map(fn (string $name): string => Str::headline($name))
                        ->all()),
                Filter::make('logged_at')
                    ->schema([
                        DatePicker::make('from')->label(__('activity.filters.from')),
                        DatePicker::make('until')->label(__('activity.filters.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date))),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading(__('activity.table.empty_heading'))
            ->emptyStateDescription(__('activity.table.empty_description'));
    }

    /**
     * Translate the three events spatie records automatically and leave
     * anything logged by hand as written.
     */
    public static function describe(?string $event): string
    {
        return match ($event) {
            null => '—',
            'created' => __('activity.events.created'),
            'updated' => __('activity.events.updated'),
            'deleted' => __('activity.events.deleted'),
            default => Str::headline($event),
        };
    }
}
