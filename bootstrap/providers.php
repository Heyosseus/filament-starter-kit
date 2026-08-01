<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\PulseServiceProvider;

return [
    AppServiceProvider::class,
    PulseServiceProvider::class,
    AdminPanelProvider::class,
];
