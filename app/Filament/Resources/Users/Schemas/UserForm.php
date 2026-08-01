<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('users.sections.profile'))
                    ->description(__('users.sections.profile_description'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('users.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('users.fields.email'))
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique('users', 'email', ignoreRecord: true),
                        DateTimePicker::make('email_verified_at')
                            ->label(__('users.fields.email_verified_at'))
                            ->helperText(__('users.form.email_verified_help')),
                    ]),
                Section::make(__('users.sections.access'))
                    ->description(__('users.sections.access_description'))
                    ->columns(2)
                    ->schema([
                        Select::make('roles')
                            ->label(__('users.fields.roles'))
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->formatStateUsing(fn (?array $state): array => $state ?? [])
                            ->getOptionLabelFromRecordUsing(fn (mixed $record): string => Str::headline($record->name)),
                        /*
                         * Left blank on edit and only written when something is
                         * actually typed, so opening a user and pressing save
                         * does not wipe their password. Hashing is left to the
                         * model's `hashed` cast rather than done here twice.
                         */
                        TextInput::make('password')
                            ->label(__('users.fields.password'))
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->helperText(fn (string $operation): ?string => $operation === 'edit'
                                ? __('users.form.password_help')
                                : null),
                    ]),
            ]);
    }
}
