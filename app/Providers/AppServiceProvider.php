<?php

declare(strict_types=1);

namespace App\Providers;

use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureLanguageSwitch();
    }

    /**
     * Fail loudly in development instead of silently doing the wrong thing:
     * accessing an unloaded relation should surface the N+1, and mass-assigning
     * an unexpected attribute should surface the typo. Both are relaxed in
     * production so a missed edge case degrades rather than 500s.
     */
    private function configureModels(): void
    {
        $isProduction = $this->app->environment('production');

        Model::shouldBeStrict(! $isProduction);

        DB::prohibitDestructiveCommands($isProduction);
    }

    /**
     * The switch is rendered inside the panel and on the login screen, so a
     * user can pick their language before they have an account to store it on.
     */
    private function configureLanguageSwitch(): void
    {
        LanguageSwitch::configureUsing(function (LanguageSwitch $switch): void {
            $switch
                ->locales(['en', 'ka'])
                ->labels([
                    'en' => 'English',
                    'ka' => 'ქართული',
                ])
                ->visible(insidePanels: true, outsidePanels: true);
        });
    }
}
