<?php

namespace App\Http\Controllers;

use App\Models\Timesheet;
use App\Models\Project;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TimesheetController extends Controller
{
    /**
     * Obtener todos los timesheets de un proyecto
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Timesheet::with(['employee', 'project', 'attendances']);

            // Filtrar por proyecto si se especifica
            if ($request->has('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            // Filtrar por fecha si se especifica
            if ($request->has('date')) {
                $date = Carbon::parse($request->date)->toDateString();
                $query->whereDate('check_in_date', $date);
            }

            // Filtrar por rango de fechas
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('check_in_date', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }

            $timesheets = $query->orderBy('check_in_date', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $timesheets,
                'message' => 'Timesheets obtenidos correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los timesheets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo timesheet
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'project_id' => 'required|exists:projects,id',
                'shift' => 'required|string|in:morning,afternoon,night',
                'check_in_date' => 'required|date',
                'break_date' => 'nullable|date|after:check_in_date',
                'end_break_date' => 'nullable|date|after:break_date',
                'check_out_date' => 'nullable|date|after:check_in_date',
            ]);

            // Verificar que el proyecto esté vigente
            $project = Project::findOrFail($request->project_id);
            $checkInDate = Carbon::parse($request->check_in_date);

            if ($checkInDate->lt(Carbon::parse($project->start_date)) ||
                $checkInDate->gt(Carbon::parse($project->end_date))) {
                return response()->json([
                    'success' => false,
                    'message' => 'La fecha del timesheet debe estar dentro del rango del proyecto'
                ], 400);
            }

            $timesheet = Timesheet::create($request->all());
            $timesheet->load(['employee', 'project']);

            return response()->json([
                'success' => true,
                'data' => $timesheet,
                'message' => 'Timesheet creado correctamente'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el timesheet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un timesheet específico
     */
    public function show($id): JsonResponse
    {
        try {
            $timesheet = Timesheet::with(['employee', 'project', 'attendances.employee'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $timesheet,
                'message' => 'Timesheet obtenido correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Timesheet no encontrado'
            ], 404);
        }
    }

    /**
     * Actualizar un timesheet
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $timesheet = Timesheet::findOrFail($id);

            $request->validate([
                'employee_id' => 'sometimes|exists:employees,id',
                'project_id' => 'sometimes|exists:projects,id',
                'shift' => 'sometimes|string|in:morning,afternoon,night',
                'check_in_date' => 'sometimes|date',
                'break_date' => 'nullable|date|after:check_in_date',
                'end_break_date' => 'nullable|date|after:break_date',
                'check_out_date' => 'nullable|date|after:check_in_date',
            ]);

            // Verificar que el proyecto esté vigente si se actualiza
            if ($request->has('project_id') || $request->has('check_in_date')) {
                $projectId = $request->project_id ?? $timesheet->project_id;
                $checkInDate = Carbon::parse($request->check_in_date ?? $timesheet->check_in_date);

                $project = Project::findOrFail($projectId);

                if ($checkInDate->lt(Carbon::parse($project->start_date)) ||
                    $checkInDate->gt(Carbon::parse($project->end_date))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La fecha del timesheet debe estar dentro del rango del proyecto'
                    ], 400);
                }
            }

            $timesheet->update($request->all());
            $timesheet->load(['employee', 'project']);

            return response()->json([
                'success' => true,
                'data' => $timesheet,
                'message' => 'Timesheet actualizado correctamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el timesheet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un timesheet
     */
    public function destroy($id): JsonResponse
    {
        try {
            $timesheet = Timesheet::findOrFail($id);

            // Verificar si tiene asistencias asociadas
            if ($timesheet->attendances()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el timesheet porque tiene asistencias asociadas'
                ], 400);
            }

            $timesheet->delete();

            return response()->json([
                'success' => true,
                'message' => 'Timesheet eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el timesheet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener timesheets por proyecto y fecha
     */
    public function getByProjectAndDate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'date' => 'required|date'
            ]);

            $date = Carbon::parse($request->date)->toDateString();

            $timesheet = Timesheet::with(['employee', 'project', 'attendances.employee'])
                ->where('project_id', $request->project_id)
                ->whereDate('check_in_date', $date)
                ->first();

            if (!$timesheet) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró timesheet para este proyecto en la fecha especificada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $timesheet,
                'message' => 'Timesheet obtenido correctamente'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el timesheet: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Búsqueda avanzada de timesheets con múltiples filtros
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'project_id' => 'nullable|exists:projects,id',
                'employee_id' => 'nullable|exists:employees,id',
                'shift' => 'nullable|in:morning,afternoon,night',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'specific_date' => 'nullable|date',
                'project_name' => 'nullable|string',
                'employee_name' => 'nullable|string',
                'has_attendances' => 'nullable|boolean',
                'attendance_count_min' => 'nullable|integer|min:0',
                'attendance_count_max' => 'nullable|integer|min:0'
            ]);

            $query = Timesheet::with(['employee', 'project.quote.client', 'attendances.employee']);

            // Filtro por proyecto específico
            if ($request->filled('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            // Filtro por empleado específico
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filtro por turno
            if ($request->filled('shift')) {
                $query->where('shift', $request->shift);
            }

            // Filtro por rango de fechas
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereBetween('check_in_date', [
                    Carbon::parse($request->date_from)->startOfDay(),
                    Carbon::parse($request->date_to)->endOfDay()
                ]);
            } elseif ($request->filled('date_from')) {
                $query->where('check_in_date', '>=', Carbon::parse($request->date_from)->startOfDay());
            } elseif ($request->filled('date_to')) {
                $query->where('check_in_date', '<=', Carbon::parse($request->date_to)->endOfDay());
            }

            // Filtro por fecha específica
            if ($request->filled('specific_date')) {
                $query->whereDate('check_in_date', Carbon::parse($request->specific_date));
            }

            // Filtro por nombre del proyecto
            if ($request->filled('project_name')) {
                $query->whereHas('project', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->project_name . '%');
                });
            }

            // Filtro por nombre del empleado
            if ($request->filled('employee_name')) {
                $query->whereHas('employee', function($q) use ($request) {
                    $q->where(function($qq) use ($request) {
                        $qq->where('first_name', 'like', '%' . $request->employee_name . '%')
                           ->orWhere('last_name', 'like', '%' . $request->employee_name . '%')
                           ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $request->employee_name . '%']);
                    });
                });
            }

            // Filtro por existencia de asistencias
            if ($request->filled('has_attendances')) {
                if ($request->has_attendances) {
                    $query->has('attendances');
                } else {
                    $query->doesntHave('attendances');
                }
            }

            // Filtro por cantidad de asistencias
            if ($request->filled('attendance_count_min')) {
                $query->has('attendances', '>=', $request->attendance_count_min);
            }
            if ($request->filled('attendance_count_max')) {
                $query->has('attendances', '<=', $request->attendance_count_max);
            }

            $timesheets = $query->orderBy('check_in_date', 'desc')->get();

            // Agregar estadísticas de la búsqueda
            $stats = [
                'total_found' => $timesheets->count(),
                'by_shift' => $timesheets->groupBy('shift')->map->count(),
                'by_project' => $timesheets->groupBy('project.name')->map->count(),
                'total_attendances' => $timesheets->sum(function($timesheet) {
                    return $timesheet->attendances->count();
                }),
                'date_range' => [
                    'earliest' => $timesheets->min('check_in_date'),
                    'latest' => $timesheets->max('check_in_date')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $timesheets,
                'statistics' => $stats,
                'message' => 'Búsqueda de timesheets completada',
                'filters_applied' => $request->only([
                    'project_id', 'employee_id', 'shift', 'date_from', 'date_to',
                    'specific_date', 'project_name', 'employee_name', 'has_attendances',
                    'attendance_count_min', 'attendance_count_max'
                ])
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }
}
