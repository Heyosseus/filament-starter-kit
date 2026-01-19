<?php

namespace App\Filament\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogResource\Pages\CreateActivityLog;
use App\Filament\Resources\ActivityLogResource\Pages\EditActivityLog;
use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-hashtag';

    public static function getNavigationGroup(): ?string
    {
        return __('SectionList.user_management');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->sortable(),
                TextColumn::make('action')
                    ->label('Action')
                    ->sortable(),
                TextColumn::make('model_type')
                    ->label('Model')
                    ->sortable(),
                TextColumn::make('model_id')
                    ->label('Model ID'),
                TextColumn::make('description')
                    ->label('Description'),
                TextColumn::make('ip_address')
                    ->label('IP Address'),
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'login' => 'Login',
                        'logout' => 'Logout',
                    ]),
                SelectFilter::make('user')
                    ->relationship('user', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'create' => CreateActivityLog::route('/create'),
            'edit' => EditActivityLog::route('/{record}/edit'),
        ];
    }
}
