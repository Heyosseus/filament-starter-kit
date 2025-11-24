<?php

namespace App\Traits;

use App\Services\ActivityLogService;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
  protected static function booted(): void
  {
    static::created(function (Model $model) {
      ActivityLogService::log('created', $model, null, $model->toArray());
    });

    static::updated(function (Model $model) {
      $changes = $model->getChanges();
      $original = $model->getOriginal();
      $oldValues = array_intersect_key($original, $changes);
      ActivityLogService::log('updated', $model, $oldValues, $changes);
    });

    static::deleted(function (Model $model) {
      ActivityLogService::log('deleted', $model, $model->toArray());
    });
  }
}
