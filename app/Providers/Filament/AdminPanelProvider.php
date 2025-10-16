<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\OrdersChartWidget;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\Facades\App;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        App::setLocale(App::getLocale());

        return $panel
            ->brandName('CrocoShop Admin')
            ->default()
            ->profile()
            ->databaseNotifications()
            ->sidebarCollapsibleOnDesktop()
            // ->topNavigation()
            ->id('admin')
            ->path('admin')
            ->login()
            ->maxContentWidth('full')
            ->colors([
                'primary' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                OrdersChartWidget::class
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ])
            ->navigationGroups([
                NavigationGroup::make(__('SectionList.user_management')),
                NavigationGroup::make(__('SectionList.delivery')),
                NavigationGroup::make(__('SectionList.operator')),
                NavigationGroup::make(__('SectionList.settings')),
                NavigationGroup::make('Filament Shield')->collapsed(),
            ]);;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
