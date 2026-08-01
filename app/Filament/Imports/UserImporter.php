<?php

declare(strict_types=1);

namespace App\Filament\Imports;

use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class UserImporter extends Importer
{
    protected static ?string $model = User::class;

    /**
     * @return array<int, ImportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->label(__('users.fields.name'))
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('email')
                ->label(__('users.fields.email'))
                ->requiredMapping()
                ->rules(['required', 'email', 'max:255']),
            ImportColumn::make('password')
                ->label(__('users.fields.password'))
                ->requiredMapping()
                ->rules(['required', 'string', 'min:8'])
                ->example('correct-horse-battery-staple'),
        ];
    }

    /**
     * Matched on email so re-running an import updates people rather than
     * failing on duplicates. The password is assigned raw — the model's
     * `hashed` cast does the hashing, and hashing here too would double-hash it.
     */
    public function resolveRecord(): ?User
    {
        $user = User::firstOrNew(['email' => $this->data['email']]);

        $user->name = $this->data['name'];
        $user->password = $this->data['password'];
        $user->email_verified_at ??= now();

        return $user;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = __('users.import.completed', [
            'count' => number_format($import->successful_rows),
        ]);

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.__('users.import.failed', [
                'count' => number_format($failedRowsCount),
            ]);
        }

        return $body;
    }

    public static function getFailedNotificationBody(Import $import): string
    {
        return __('users.import.all_failed', [
            'count' => number_format($import->getFailedRowsCount()),
        ]);
    }
}
