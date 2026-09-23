<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\RequestConsolidatedController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\RequirementListController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\SubClientDataController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\VisitReportPdfController;
use App\Http\Controllers\WorkReportConsolidatedController;
use App\Http\Controllers\WorkReportPdfController;
use App\Http\Controllers\WorkReportWordController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

// Redirigir la raíz al dashboard de Filament
Route::redirect('/', '/dashboard');

// Búsqueda de subclientes para componentes internos autenticados
Route::get('/sub-clients/search', [SubClientDataController::class, 'index'])
    ->name('sub-clients.search')
    ->middleware('auth');

// Búsqueda, creación y subida en cola de fotos para componentes internos autenticados
Route::prefix('items')->middleware('auth')->group(function () {
    Route::get('/search', [ItemController::class, 'index'])->name('items.search');
    Route::get('/', [ItemController::class, 'index'])->name('items.web.index');
    Route::post('/', [ItemController::class, 'store'])->name('items.store');
    Route::post('/{item}/photo/queue', [ItemController::class, 'queuePhotoUpload'])->name('items.photo.queue');
    Route::post('/photo/queue', [ItemController::class, 'queuePhotoUpload'])->name('items.photo.queue.general');
});

// Búsqueda y listado de unidades para componentes internos autenticados
Route::get('/units', [UnitController::class, 'index'])
    ->name('units.search')
    ->middleware('auth');

// Categorías y Subcategorías para componentes internos autenticados (solo index y store)
Route::prefix('categories')->middleware('auth')->group(function () {
    Route::get('/', [CategoryController::class, 'index'])->name('categories.web.index');
    Route::get('/search', [CategoryController::class, 'index'])->name('categories.search');
    Route::post('/', [CategoryController::class, 'store'])->name('categories.store');
});

Route::prefix('subcategories')->middleware('auth')->group(function () {
    Route::get('/', [SubcategoryController::class, 'index'])->name('subcategories.web.index');
    Route::get('/search', [SubcategoryController::class, 'index'])->name('subcategories.search');
    Route::post('/', [SubcategoryController::class, 'store'])->name('subcategories.store');
});

// Requerimientos para componentes internos autenticados
Route::prefix('requirements')->middleware('auth')->group(function () {
    Route::post('/', [RequirementController::class, 'store'])->name('requirements.web.store');
    Route::put('/{requirement}', [RequirementController::class, 'update'])->name('requirements.web.update');
    Route::get('/{requirement}/items', [RequirementListController::class, 'index'])->name('requirements.items.index');
    Route::post('/{requirement}/items', [RequirementListController::class, 'store'])->name('requirements.items.store');
    Route::delete('/{requirement}/items', [RequirementListController::class, 'clear'])->name('requirements.items.clear');
    Route::put('/{requirement}/items/{item}', [RequirementListController::class, 'update'])->name('requirements.items.update');
    Route::delete('/{requirement}/items/{item}', [RequirementListController::class, 'destroy'])->name('requirements.items.destroy');
});

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

// Route::get('/work-report/{workReport}/word', [WorkReportWordController::class, 'generateReport'])
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
