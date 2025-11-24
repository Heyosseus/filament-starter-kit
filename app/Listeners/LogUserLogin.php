<?php

namespace App\Listeners;

use App\Services\ActivityLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Request;

class LogUserLogin
{
  public function handle(Login $event): void
  {
    ActivityLogService::log('login', null, null, null, 'User logged in');
  }
}
