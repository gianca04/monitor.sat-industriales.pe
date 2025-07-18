<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::post('/login', [AuthController::class, 'login']);
Route::get('/status', function () {
    return response()->json(['status' => 'OK', 'message' => 'El sistema está funcionando correctamente']);
});

// Rutas protegidas con autenticación
Route::middleware(['auth:sanctum', 'CheckTokenExpiration'])->group(function () {

    // Autenticación
    Route::post('/logout', [AuthController::class, 'logout']);

    // Proyectos
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']); // Proyectos vigentes
        Route::get('/all', [ProjectController::class, 'all']); // Todos los proyectos
        Route::get('/search', [ProjectController::class, 'search']); // Búsqueda avanzada
        Route::get('/{id}', [ProjectController::class, 'show']);
    });

    // Empleados
    Route::prefix('employees')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('/search', [EmployeeController::class, 'search']); // Búsqueda avanzada
        Route::get('/available/project', [EmployeeController::class, 'getAvailableForProject']);
        Route::post('/', [EmployeeController::class, 'store']);
        Route::get('/{id}', [EmployeeController::class, 'show']);
        Route::put('/{id}', [EmployeeController::class, 'update']);
        Route::delete('/{id}', [EmployeeController::class, 'destroy']);
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

    // Asistencias
    Route::prefix('attendances')->group(function () {
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('/search', [AttendanceController::class, 'search']); // Búsqueda avanzada
        Route::post('/', [AttendanceController::class, 'store']);
        Route::post('/bulk', [AttendanceController::class, 'bulkStore']);
        Route::get('/stats/{timesheetId}', [AttendanceController::class, 'getStats']);
        Route::get('/{id}', [AttendanceController::class, 'show']);
        Route::put('/{id}', [AttendanceController::class, 'update']);
        Route::delete('/{id}', [AttendanceController::class, 'destroy']);
        Route::post('/{id}/restore', [AttendanceController::class, 'restore']);
    });

    // Reportes y estadísticas
    Route::prefix('reports')->group(function () {
        Route::get('/dashboard', [ReportController::class, 'dashboard']);
        Route::get('/attendance', [ReportController::class, 'attendanceReport']);
        Route::get('/employee-productivity', [ReportController::class, 'employeeProductivity']);
        Route::get('/project-timesheets', [ReportController::class, 'projectTimesheets']);
    });
});
