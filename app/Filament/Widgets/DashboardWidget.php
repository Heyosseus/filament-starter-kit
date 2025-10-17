<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Role;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardWidget extends BaseWidget
{

  protected static ?int $sort = 1;


  protected function getStats(): array
  {
    $cacheDuration = now()->addMinutes(60);

    $roleCount = Cache::remember('role_count', $cacheDuration, function () {
      return Role::query()->count();
    });

    $totalUsers = Cache::remember('total_users', $cacheDuration, function () {
      return User::query()->count();
    });

    if (!session()->has('session_start_time')) {
      session()->put('session_start_time', now()->timestamp);
    }

    $sessionStartTime = session()->get('session_start_time');
    $now = now()->timestamp;
    $durationSeconds = $now - $sessionStartTime;

    $hours = floor($durationSeconds / 3600);
    $minutes = floor(($durationSeconds % 3600) / 60);
    $seconds = $durationSeconds % 60;

    if ($hours > 0) {
      $sessionTime = sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
    } else {
      $sessionTime = sprintf('%d:%02d', $minutes, $seconds);
    }


    return [
      Stat::make(__('Widgets.total_users'), $totalUsers)
        ->description(__('Widgets.total_users'))
        ->descriptionIcon('heroicon-o-users', IconPosition::Before)
        ->color('success'),

      Stat::make(__('Widgets.roles'), $roleCount)
        ->description(__('Widgets.filament_shield_roles'))
        ->descriptionIcon('heroicon-o-shield-check', IconPosition::Before)
        ->color('info'),

      Stat::make(__('Widgets.session'), $sessionTime)
        ->description(__('Widgets.session_duration'))
        ->descriptionIcon('heroicon-o-clock', IconPosition::Before)
        ->color('warning'),
    ];
  }
}
