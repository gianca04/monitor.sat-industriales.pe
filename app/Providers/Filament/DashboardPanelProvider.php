<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\SpatieLaravelTranslatablePlugin;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('dashboard')
            ->font('Poppins')
            ->path('dashboard')
            ->login()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            ->databaseNotifications()
            ->databaseNotificationsPolling('30s') // Actualizar cada 30 segundos
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
            ->plugins([
                FilamentShieldPlugin::make(),
                SpatieLaravelTranslatablePlugin::make()
                    ->defaultLocales(['es'])
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->colors([
                'primary' => [
                    50 => 'rgb(213, 242, 239)',
                    100 => 'rgb(161, 222, 214)',
                    200 => 'rgb(103, 196, 186)',
                    300 => 'rgb(59, 166, 160)',
                    400 => 'rgb(42, 141, 141)',
                    500 => 'rgb(41, 129, 130)', // color base
                    600 => 'rgb(34, 111, 112)',
                    700 => 'rgb(29, 92, 92)',
                    800 => 'rgb(24, 76, 76)',
                    900 => 'rgb(20, 65, 65)',
                    950 => 'rgb(12, 38, 38)',
                ],

                'secondary' => [
                    50 => 'rgb(241, 226, 222)',
                    100 => 'rgb(228, 202, 193)',
                    200 => 'rgb(208, 165, 154)',
                    300 => 'rgb(183, 126, 115)',
                    400 => 'rgb(145, 92, 82)',
                    500 => 'rgb(109, 66, 58)',
                    600 => 'rgb(94, 57, 50)',
                    700 => 'rgb(74, 45, 39)',
                    800 => 'rgb(56, 33, 29)',
                    900 => 'rgb(40, 22, 20)',
                    950 => 'rgb(23, 13, 12)',
                ],

                'accent' => [
                    50 => 'rgb(243, 224, 210)',
                    100 => 'rgb(232, 201, 183)',
                    200 => 'rgb(218, 173, 147)',
                    300 => 'rgb(197, 141, 113)',
                    400 => 'rgb(168, 98, 65)',
                    500 => 'rgb(137, 67, 35)',
                    600 => 'rgb(117, 57, 30)',
                    700 => 'rgb(92, 44, 23)',
                    800 => 'rgb(69, 33, 17)',
                    900 => 'rgb(51, 25, 13)',
                    950 => 'rgb(29, 14, 8)',
                ],

                'neutral' => [
                    100 => 'rgb(246, 233, 209)',
                    200 => 'rgb(237, 219, 183)',
                    300 => 'rgb(222, 199, 150)',
                    400 => 'rgb(198, 169, 110)',
                    500 => 'rgb(178, 147, 85)',
                    600 => 'rgb(160, 132, 68)',
                    700 => 'rgb(136, 113, 51)',
                    800 => 'rgb(110, 91, 37)',
                    900 => 'rgb(86, 72, 26)',
                    950 => 'rgb(53, 43, 15)',
                ],
            ])

            // Sidebar
            ->sidebarCollapsibleOnDesktop()
            // Logo de SAT Industriales
            ->brandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('2rem')
            ->favicon(asset('images/favicon.svg'))
        ;
    }
}
