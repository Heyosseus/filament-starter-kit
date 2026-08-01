<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('users.sections.profile'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('name')
                            ->label(__('users.fields.name'))
                            ->weight('medium'),
                        TextEntry::make('email')
                            ->label(__('users.fields.email'))
                            ->copyable(),
                        TextEntry::make('email_verified_at')
                            ->label(__('users.fields.verified'))
                            ->badge()
                            ->color(fn (?string $state): string => $state === null ? 'warning' : 'success')
                            ->formatStateUsing(fn (?string $state): string => $state === null
                                ? __('users.table.unverified')
                                : __('users.table.verified')),
                    ]),
                Section::make(__('users.sections.access'))
                    ->columns(3)
                    ->schema([
                        TextEntry::make('roles.name')
                            ->label(__('users.fields.roles'))
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => Str::headline($state))
                            ->placeholder(__('users.table.no_roles')),
                        TextEntry::make('created_at')
                            ->label(__('users.fields.created_at'))
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label(__('users.fields.updated_at'))
                            ->since()
                            ->tooltip(fn (User $record): ?string => $record->updated_at?->toDateTimeString()),
                    ]),
            ]);
    }
}
