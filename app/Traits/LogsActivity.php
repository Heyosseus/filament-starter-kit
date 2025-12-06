<?php

namespace App\Traits;

use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static function booted(): void
    {
        static::created(function (Model $model): void {
            ActivityLogService::log('created', $model, null, $model->toArray());
        });

        static::updated(function (Model $model): void {
            $changes = $model->getChanges();
            $original = $model->getOriginal();
            $oldValues = array_intersect_key($original, $changes);
            ActivityLogService::log('updated', $model, $oldValues, $changes);
        });

        static::deleted(function (Model $model): void {
            ActivityLogService::log('deleted', $model, $model->toArray());
        });
    }
}
