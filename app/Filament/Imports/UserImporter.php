<?php

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;

class UserImporter extends Importer
{
  protected static ?string $model = User::class;

  public static function getColumns(): array
  {
    return [
      ImportColumn::make('name')
        ->requiredMapping()
        ->rules(['required', 'max:255']),

      ImportColumn::make('email')
        ->requiredMapping()
        ->rules(['required', 'email', 'max:255', 'unique:users,email']),

      ImportColumn::make('password')
        ->requiredMapping()
        ->rules(['required', 'min:8'])
        ->example('password123'),
    ];
  }

  public function resolveRecord(): ?User
  {
    $data = $this->data;

    // Hash the password before creating/updating
    if (isset($data['password'])) {
      $data['password'] = Hash::make($data['password']);
    }

    // Set email verification timestamp
    $data['email_verified_at'] = now();

    return User::firstOrNew(
      ['email' => $data['email']],
      $data
    );
  }

  public static function getCompletedNotificationBody(Import $import): string
  {
    $body = 'Your user import has completed and ' . number_format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

    if ($failedRowsCount = $import->getFailedRowsCount()) {
      $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
    }

    return $body;
  }

  public static function getFailedNotificationBody(Import $import): string
  {
    return 'Your user import has failed and ' . number_format($import->getFailedRowsCount()) . ' ' . str('row')->plural($import->getFailedRowsCount()) . ' failed to import.';
  }

  public static function getOptionsFormComponents(): array
  {
    return [
      //
    ];
  }
}
