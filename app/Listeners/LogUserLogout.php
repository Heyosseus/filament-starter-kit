<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Logout;

class LogUserLogout
{
  public function handle(Logout $event): void
  {
    ActivityLogService::log('logout', null, null, null, 'User logged out');
  }
}
