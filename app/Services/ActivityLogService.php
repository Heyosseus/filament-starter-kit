<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    public static function log(string $action, ?Model $model = null, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): void
    {
        $user = auth()->user();
        if (! $user) {
            return; // Only log for authenticated users
        }

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'model_type' => $model instanceof \Illuminate\Database\Eloquent\Model ? $model::class : null,
            'model_id' => $model instanceof \Illuminate\Database\Eloquent\Model ? $model->getKey() : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'description' => $description,
        ]);
    }
}
