<?php

namespace App\Filament\Resources;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\ImportAction;
use Filament\Actions\ExportAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $title = 'name';

    protected static string | \UnitEnum | null $navigationGroup;

    public static function getNavigationGroup(): ?string
    {
        return __('SectionList.user_management');
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('BaseForm.Name'))
                    ->maxLength(255)
                    ->required(),
                TextInput::make('email')
                    ->label(__('BaseForm.Email'))
                    ->email()
                    ->unique(
                        'users',
                        'email',
                        ignoreRecord: true,
                    )
                    ->maxLength(255)
                    ->required(),
                Select::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->default(['4']) // panel_user
                    ->searchable(),
                TextInput::make('password')
                    ->password()
                    ->label(__('BaseForm.Password'))
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('BaseForm.Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('BaseForm.Email'))
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name') // Add this line
                    ->label(__('BaseForm.roles'))
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('BaseForm.created_at'))
                    ->searchable()
                    ->sortable()
                    ->date(),
                TextColumn::make('updated_at')
                    ->label(__('BaseForm.updated_at'))
                    ->copyable()
                    ->searchable()
                    ->sortable()
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->headerActions([
                ImportAction::make()
                    ->importer(UserImporter::class)
                    ->label(__('BaseForm.import'))
                    ->icon('heroicon-o-cloud-arrow-down'),
                ExportAction::make()
                    ->exporter(UserExporter::class)
                    ->label(__('BaseForm.export'))
                    ->icon('heroicon-o-cloud-arrow-up'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('SectionList.admin');
    }
}
