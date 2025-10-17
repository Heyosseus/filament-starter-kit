<?php

namespace App\Filament\Resources;

use App\Enums\RoleEnums;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ImportAction;
use Illuminate\Support\Facades\Hash;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

use App\Filament\Resources\RoleResource;

class UserResource extends Resource implements HasShieldPermissions

{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $title = 'name';

    protected static ?string $navigationGroup;

    public static function getNavigationGroup(): ?string
    {
        return
            __('SectionList.user_management');
    }
    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
        ];
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('BaseForm.Name'))
                    ->maxLength(255)
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->label(__('BaseForm.Email'))
                    ->email()
                    ->unique(
                        'users',
                        'email',
                        ignoreRecord: true,
                    )
                    ->maxLength(255)
                    ->required(),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->default(['4']) // panel_user
                    ->searchable(),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->label(__('BaseForm.Password'))
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('BaseForm.Name'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label(__('BaseForm.Email'))
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name') // Add this line
                    ->label(__('BaseForm.roles'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('BaseForm.created_at'))
                    ->searchable()
                    ->sortable()
                    ->date(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('BaseForm.updated_at'))
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(UserImporter::class)
                    ->label('Import')
                    ->icon('heroicon-o-cloud-arrow-down'),
                ExportAction::make()
                    ->exporter(UserExporter::class)
                    ->label('Export')
                    ->icon('heroicon-o-cloud-arrow-up'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('SectionList.admin');
    }
}
