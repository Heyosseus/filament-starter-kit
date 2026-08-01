<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Pulse ships a gate that only opens in the local environment. That is the
 * right default but the wrong behaviour for a deployed panel, so the gate is
 * redefined here against a Shield permission instead.
 */
class PulseServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('viewPulse', function (?User $user): bool {
            if ($user === null) {
                return false;
            }

            if (Utils::isSuperAdminEnabled() && $user->hasRole(Utils::getSuperAdminName())) {
                return true;
            }

            return $user->can('view_pulse');
        });
    }
}
