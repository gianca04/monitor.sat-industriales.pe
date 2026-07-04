<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \App\Models\Photo::observe(\App\Observers\PhotoObserver::class);
        \App\Models\Epp::observe(\App\Observers\EppObserver::class);
        \App\Models\Delivery::observe(\App\Observers\DeliveryObserver::class);

        FilamentView::registerRenderHook(
            'panels::auth.login.form.after',
            fn (): string => Blade::render('@vite(\'resources/css/custom-login.css\')'),
        );
    }
}
