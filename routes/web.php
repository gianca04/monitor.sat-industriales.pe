<?php

use App\Http\Controllers\VisitReportPdfController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkReportPdfController;
use App\Http\Controllers\WorkReportConsolidatedController;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use App\Http\Controllers\WorkReportWordController;
use App\Http\Controllers\RequestConsolidatedController;


// Redirigir la raíz al dashboard de Filament
Route::redirect('/', '/dashboard');

// Ruta para generar reporte PDF de trabajo
Route::get('/work-report/{workReport}/pdf', [WorkReportPdfController::class, 'generateReport'])
    ->name('work-report.pdf')
    ->middleware('auth');

// Ruta para generar reporte PDF de trabajo
Route::get('/visit-report/{workReport}/pdf', [VisitReportPdfController::class, 'generateReport'])
    ->name('visit-report.pdf')
    ->middleware('auth');

// Rutas para reporte consolidado de trabajo por proyecto
Route::prefix('project/{project}')->middleware('auth')->group(function () {
    Route::get('/consolidated-report/pdf', [WorkReportConsolidatedController::class, 'generateConsolidatedReport'])
        ->name('project.consolidated-report.pdf');

    Route::get('/consolidated-report/preview', [WorkReportConsolidatedController::class, 'previewConsolidatedReport'])
        ->name('project.consolidated-report.preview');

    Route::get('/consolidated-report/statistics', [WorkReportConsolidatedController::class, 'getConsolidatedStatistics'])
        ->name('project.consolidated-report.statistics');
});

// Rutas para reporte consolidado de visitas por request
Route::prefix('request/{request}')->middleware('auth')->group(function () {
    Route::get('/consolidated-report/pdf', [RequestConsolidatedController::class, 'generateConsolidatedReport'])
        ->name('request.consolidated-report.pdf');

    Route::get('/consolidated-report/preview', [RequestConsolidatedController::class, 'previewConsolidatedReport'])
        ->name('request.consolidated-report.preview');

    Route::get('/consolidated-report/statistics', [RequestConsolidatedController::class, 'getConsolidatedStatistics'])
        ->name('request.consolidated-report.statistics');
});

// Las rutas de Livewire y Filament se configuran automáticamente
// a través del DashboardPanelProvider

//Route::get('/work-report/{workReport}/word', [WorkReportWordController::class, 'generateReport'])
//    ->name('work-report.word')
//    ->middleware('auth');

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
