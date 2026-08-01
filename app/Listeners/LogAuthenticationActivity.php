<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;

/**
 * Signing in and out are not model changes, so nothing records them
 * automatically — this puts them in the same audit trail as everything else.
 *
 * Laravel discovers listeners in app/Listeners by the type hint on handle(),
 * so these need no registration.
 */
class LogAuthenticationActivity
{
    public function __construct(private readonly Request $request) {}

    public function handleLogin(Login $event): void
    {
        $this->record($event->user, 'login');
    }

    public function handleLogout(Logout $event): void
    {
        $this->record($event->user, 'logout');
    }

    /**
     * A failed attempt has no authenticated user to attribute it to, so the
     * address that tried and the identifier it tried are the whole record.
     */
    public function handleFailed(Failed $event): void
    {
        activity('auth')
            ->event('login_failed')
            ->withProperties([
                'email' => $event->credentials['email'] ?? null,
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ])
            ->log('login_failed');
    }

    private function record(?Authenticatable $user, string $event): void
    {
        if ($user === null) {
            return;
        }

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->event($event)
            ->withProperties([
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
            ])
            ->log($event);
    }
}
