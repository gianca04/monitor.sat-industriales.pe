<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
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

        // Configuración de Rate Limiters para la API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Demasiadas solicitudes de inicio de sesión. Por favor, intente de nuevo más tarde.',
                ], 429, $headers);
            });
        });

        RateLimiter::for('pdf_generation', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip())->response(function (Request $request, array $headers) {
                return response()->json([
                    'message' => 'Demasiadas solicitudes de generación de PDF. Por favor, intente de nuevo en un minuto.',
                ], 429, $headers);
            });
        });
    }
}
