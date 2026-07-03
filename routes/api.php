<?php

use App\Http\Controllers\PhotoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDataController;
use App\Http\Controllers\ClientDataController;
use App\Http\Controllers\SubClientDataController;
use App\Http\Controllers\PositionController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\WorkReportPdfController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\WorkReportController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\QuoteController as ControllersQuoteController;
use App\Models\SubClient;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);
Route::get('/status', function () {
    return response()->json(['status' => 'OK', 'message' => 'El sistema está funcionando correctamente']);
});

// TODO: Remover o mover este endpoint temporal para pruebas unitarias de firmas en Base64
// Route::post('/test-signature', function (Illuminate\Http\Request $request) {
//     $base64 = $request->input('signature');
//     if ($base64 && str_starts_with($base64, 'data:image/png;base64,')) {
//         return response()->json([
//             'success' => true,
//             'message' => 'Valid base64 received',
//             'length' => strlen($base64)
//         ]);
//     }
//     return response()->json(['success' => false, 'message' => 'Invalid base64'], 400);
// });

// Rutas protegidas con autenticación
Route::middleware(['auth:sanctum', 'CheckTokenExpiration'])->group(function () {

    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout']);


    // Proyectos
    Route::prefix('projects')->group(function () {
        // Endpoint principal para proyectos vigentes y búsqueda por nombre, cliente y subcliente
        Route::get('/', [ProjectController::class, 'index']);
        Route::get('/quick-search', [ProjectController::class, 'quickSearch']); // Búsqueda rápida
        Route::get('/sync', [ProjectController::class, 'syncProjects']);

        Route::get('/{id}', [ProjectController::class, 'show']);
    });

    // Empleados
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('/search', [EmployeeController::class, 'search']); // Búsqueda avanzada
        Route::get('/available/project', [EmployeeController::class, 'getAvailableForProject']);

        Route::get('/quick-search', [EmployeeController::class, 'quickSearch']); // Búsqueda rápida

        // Endpoints para transferencia masiva/por lotes de datos de empleados
        Route::get('/data', [EmployeeDataController::class, 'index']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/{id}', [EmployeeController::class, 'show']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
    });


    // Clientes
    Route::prefix('clients')->group(function () {
        // Endpoints para transferencia masiva/por lotes de datos de clientes
        Route::get('/data', [ClientDataController::class, 'index']);
    });

    // SubClientes
    Route::prefix('sub-clients')->group(function () {
        // Endpoints para transferencia masiva/por lotes de datos de subclientes
        Route::get('/data', [SubClientDataController::class, 'index']); // ⬅️ ESTE SÍ SE USA
    });

    // Timesheets
    Route::prefix('timesheets')->group(function () {
        Route::get('/', [TimesheetController::class, 'index']);
        Route::get('/search', [TimesheetController::class, 'search']); // Búsqueda avanzada
        Route::post('/', [TimesheetController::class, 'store']);
        Route::get('/project-date', [TimesheetController::class, 'getByProjectAndDate']);
        Route::get('/{id}', [TimesheetController::class, 'show']);
        Route::put('/{id}', [TimesheetController::class, 'update']);
        Route::delete('/{id}', [TimesheetController::class, 'destroy']);
    });

    // Reportes y estadísticas
    Route::prefix('reports')->group(function () {
        Route::get('/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/attendance', [ReportController::class, 'attendanceReport']);
        Route::get('/employee-productivity', [ReportController::class, 'employeeProductivity']);
        Route::get('/project-timesheets', [ReportController::class, 'projectTimesheets']);
    });

    // Work Reports
    Route::prefix('work-reports')->group(function () {
        Route::get('/', [WorkReportController::class, 'index']);
        Route::post('/', [WorkReportController::class, 'store']);
        Route::get('/project/{projectId}', [WorkReportController::class, 'getByProject']);
        Route::get('/employee/{employeeId}', [WorkReportController::class, 'getByEmployee']);
        Route::get('/{id}', [WorkReportController::class, 'show']);
        Route::put('/{id}', [WorkReportController::class, 'update']); // Usar POST con _method=PUT para archivos
        Route::delete('/{id}', [WorkReportController::class, 'destroy']);
    });

    // Photos:
    Route::apiResource('photos', PhotoController::class);
    Route::apiResource('positions', PositionController::class);

    // Ruta para generar reporte PDF de trabajo
    Route::get('/work-report/{workReport}/pdf', [WorkReportPdfController::class, 'generateReport'])
        ->name('api.work-report.pdf');
});