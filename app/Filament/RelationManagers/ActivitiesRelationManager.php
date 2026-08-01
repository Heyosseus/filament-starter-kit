<?php

declare(strict_types=1);

namespace App\Filament\RelationManagers;

use App\Filament\Resources\Activities\ActivityResource;
use App\Filament\Resources\Activities\Tables\ActivitiesTable;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * A record's own history, mounted read-only on its resource. Reusable by any
 * resource whose model uses LogsActivity — the relationship is named
 * `activities` on all of them.
 */
class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static string|BackedEnum|null $icon = Heroicon::OutlinedClock;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('activity.relation.title');
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
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
                    ->formatStateUsing(fn (?string $state): string => ActivitiesTable::describe($state)),
                TextColumn::make('causer.name')
                    ->label(__('activity.fields.by'))
                    ->placeholder(__('activity.system'))
                    ->weight('medium'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('activity.relation.open'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->color('gray')
                    ->url(fn (Activity $record): string => ActivityResource::getUrl('view', ['record' => $record])),
            ])
            ->emptyStateHeading(__('activity.relation.empty'));
    }
}
