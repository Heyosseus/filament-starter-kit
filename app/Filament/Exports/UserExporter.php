<?php

declare(strict_types=1);

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    /**
     * @return array<int, ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label(__('users.fields.id')),
            ExportColumn::make('name')
                ->label(__('users.fields.name')),
            ExportColumn::make('email')
                ->label(__('users.fields.email')),
            ExportColumn::make('roles.name')
                ->label(__('users.fields.roles')),
            ExportColumn::make('email_verified_at')
                ->label(__('users.fields.email_verified_at')),
            ExportColumn::make('created_at')
                ->label(__('users.fields.created_at')),
            ExportColumn::make('updated_at')
                ->label(__('users.fields.updated_at')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = __('users.export.completed', [
            'count' => number_format($export->successful_rows),
        ]);

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.__('users.export.failed', [
                'count' => number_format($failedRowsCount),
            ]);
        }

        return $body;
    }
}
