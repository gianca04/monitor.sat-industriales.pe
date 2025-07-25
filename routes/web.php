<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkReportPdfController;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use App\Http\Controllers\WorkReportWordController;


// Redirigir la raíz al dashboard de Filament
Route::redirect('/', '/dashboard');

// Ruta para generar reporte PDF de trabajo
Route::get('/work-report/{workReport}/pdf', [WorkReportPdfController::class, 'generateReport'])
    ->name('work-report.pdf')
    ->middleware('auth');

// Las rutas de Livewire y Filament se configuran automáticamente
// a través del DashboardPanelProvider

Route::get('/work-report/{workReport}/word', [WorkReportWordController::class, 'generateReport'])
    ->name('work-report.word')
    ->middleware('auth');

Livewire::setScriptRoute(function ($handle) {
    return Route::get('/monitor.sat-industriales.pe/public/livewire/livewire.js', $handle);
});

Livewire::setUpdateRoute(function ($handle) {
    return Route::post('/monitor.sat-industriales.pe/public/livewire/update', $handle);
});

Route::get('/storage-link', function () {
    Artisan::call('storage:link');
});


Route::get('/crear-symlink', function () {
    $target = storage_path('app/public');
    $link = public_path('storage');

    if (file_exists($link)) {
        return '⚠️ Ya existe un enlace o carpeta llamado "storage" en public.';
    }

    if (symlink($target, $link)) {
        return '✅ Enlace simbólico creado correctamente.';
    } else {
        return '❌ No se pudo crear el enlace simbólico.';
    }
});
