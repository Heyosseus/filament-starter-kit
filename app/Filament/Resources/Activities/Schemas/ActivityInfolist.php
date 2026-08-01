<?php

declare(strict_types=1);

namespace App\Filament\Resources\Activities\Schemas;

use App\Filament\Resources\Activities\Tables\ActivitiesTable;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * A single audit entry in full: who did what, and the field-by-field diff of
 * what the change actually did.
 */
class ActivityInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('activity.sections.summary'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('activity.fields.when'))
                            ->dateTime(),
                        TextEntry::make('event')
                            ->label(__('activity.fields.event'))
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => ActivitiesTable::describe($state)),
                        TextEntry::make('log_name')
                            ->label(__('activity.fields.area'))
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn (?string $state): string => $state === null ? '—' : Str::headline($state)),
                        TextEntry::make('subject_type')
                            ->label(__('activity.fields.subject'))
                            ->formatStateUsing(fn (?string $state, Activity $record): string => $state === null
                                ? '—'
                                : Str::headline(class_basename($state)).($record->subject_id !== null ? " #{$record->subject_id}" : '')),
                        TextEntry::make('causer.name')
                            ->label(__('activity.fields.by'))
                            ->placeholder(__('activity.system')),
                        TextEntry::make('batch_uuid')
                            ->label(__('activity.fields.batch'))
                            ->placeholder('—')
                            ->copyable(),
                    ]),
                Section::make(__('activity.sections.changes'))
                    ->visible(fn (Activity $record): bool => self::changes($record) !== [])
                    ->schema([
                        RepeatableEntry::make('changes')
                            ->hiddenLabel()
                            ->state(fn (Activity $record): array => self::changes($record))
                            ->columns(3)
                            ->schema([
                                TextEntry::make('field')
                                    ->label(__('activity.fields.field'))
                                    ->weight('medium'),
                                TextEntry::make('old')
                                    ->label(__('activity.fields.old'))
                                    ->placeholder('—')
                                    ->color('danger'),
                                TextEntry::make('new')
                                    ->label(__('activity.fields.new'))
                                    ->placeholder('—')
                                    ->color('success'),
                            ]),
                    ]),
                Section::make(__('activity.sections.properties'))
                    ->visible(fn (Activity $record): bool => self::changes($record) === []
                        && $record->properties !== null
                        && $record->properties->isNotEmpty())
                    ->schema([
                        TextEntry::make('properties')
                            ->hiddenLabel()
                            ->state(fn (Activity $record): string => (string) json_encode(
                                $record->properties?->toArray() ?? [],
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                            ))
                            ->fontFamily('mono')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * The field-by-field diff, normalised into rows the infolist can repeat
     * over. The diff lives in `attribute_changes`; `properties` is a separate
     * bag for anything logged by hand.
     *
     * @return list<array{field: string, old: string, new: string}>
     */
    private static function changes(Activity $record): array
    {
        $changes = $record->attribute_changes;

        if ($changes === null) {
            return [];
        }

        $new = $changes->get('attributes', []);
        $old = $changes->get('old', []);
        $new = is_array($new) ? $new : [];
        $old = is_array($old) ? $old : [];

        if ($new === [] && $old === []) {
            return [];
        }

        $fields = array_unique([...array_keys($new), ...array_keys($old)]);
        $rows = [];

        foreach ($fields as $field) {
            $rows[] = [
                'field' => (string) Str::headline((string) $field),
                'old' => self::scalar($old[$field] ?? null),
                'new' => self::scalar($new[$field] ?? null),
            ];
        }

        return $rows;
    }

    private static function scalar(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
