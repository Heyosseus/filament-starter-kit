<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Tables;

use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ExportBulkAction;
use Filament\Actions\ImportAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('roles'))
            ->columns([
                TextColumn::make('name')
                    ->label(__('users.fields.name'))
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('users.fields.email'))
                    ->copyable()
                    ->copyMessage(__('users.table.email_copied'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label(__('users.fields.roles'))
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (string $state): string => Str::headline($state))
                    ->placeholder(__('users.table.no_roles')),
                TextColumn::make('email_verified_at')
                    ->label(__('users.fields.verified'))
                    ->badge()
                    ->color(fn (?string $state): string => $state === null ? 'warning' : 'success')
                    ->formatStateUsing(fn (?string $state): string => $state === null
                        ? __('users.table.unverified')
                        : __('users.table.verified'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('users.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label(__('users.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('roles')
                    ->label(__('users.filters.role'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                Filter::make('unverified')
                    ->label(__('users.filters.unverified'))
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at'))
                    ->toggle(),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(UserImporter::class)
                    ->label(__('users.actions.import'))
                    ->icon('heroicon-o-arrow-down-tray'),
                ExportAction::make()
                    ->exporter(UserExporter::class)
                    ->label(__('users.actions.export'))
                    ->icon('heroicon-o-arrow-up-tray'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(UserExporter::class)
                        ->label(__('users.actions.export')),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('users.table.empty_heading'))
            ->emptyStateDescription(__('users.table.empty_description'));
    }
}
