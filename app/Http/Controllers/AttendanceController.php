<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Timesheet;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Obtener todas las asistencias de un timesheet
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Attendance::with(['employee', 'timesheet.project']);

            // Filtrar por timesheet si se especifica
            if ($request->has('timesheet_id')) {
                $query->where('timesheet_id', $request->timesheet_id);
            }

            // Filtrar por empleado si se especifica
            if ($request->has('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filtrar por estado si se especifica
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $attendances = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $attendances,
                'message' => 'Asistencias obtenidas correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las asistencias: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva asistencia
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'timesheet_id' => 'required|exists:timesheets,id',
                'employee_id' => 'required|exists:employees,id',
                'status' => 'required|string|in:present,absent,late,permission,sick_leave',
                'shift' => 'required|string|in:day,night',
                'check_in_date' => 'nullable|date',
                'break_date' => 'nullable|date|after:check_in_date',
                'end_break_date' => 'nullable|date|after:break_date',
                'check_out_date' => 'nullable|date|after:check_in_date',
                'observation' => 'nullable|string|max:500',
            ]);

            // Verificar que no exista ya una asistencia para este empleado en este timesheet
            $existingAttendance = Attendance::where('timesheet_id', $request->timesheet_id)
                ->where('employee_id', $request->employee_id)
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe una asistencia registrada para este empleado en este timesheet'
                ], 400);
            }

            // Verificar que el timesheet existe y obtener su información
            $timesheet = Timesheet::findOrFail($request->timesheet_id);

            // Si no se proporciona información de horarios y el estado es 'present',
            // usar los horarios del timesheet
            $attendanceData = $request->all();
            if ($request->status === 'present' && !$request->check_in_date) {
                $attendanceData['check_in_date'] = $timesheet->check_in_date;
                $attendanceData['break_date'] = $timesheet->break_date;
                $attendanceData['end_break_date'] = $timesheet->end_break_date;
                $attendanceData['check_out_date'] = $timesheet->check_out_date;
            }

            $attendance = Attendance::create($attendanceData);
            $attendance->load(['employee', 'timesheet.project']);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Asistencia registrada correctamente'
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
                'message' => 'Error al registrar la asistencia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una asistencia específica
     */
    public function show($id): JsonResponse
    {
        try {
            $attendance = Attendance::with(['employee', 'timesheet.project'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Asistencia obtenida correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Asistencia no encontrada'
            ], 404);
        }
    }

    /**
     * Actualizar una asistencia
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $attendance = Attendance::findOrFail($id);

            $request->validate([
                'status' => 'sometimes|string|in:present,absent,late,permission,sick_leave',
                'shift' => 'sometimes|string|in:morning,afternoon,night',
                'check_in_date' => 'nullable|date',
                'break_date' => 'nullable|date|after:check_in_date',
                'end_break_date' => 'nullable|date|after:break_date',
                'check_out_date' => 'nullable|date|after:check_in_date',
                'observation' => 'nullable|string|max:500',
            ]);

            $attendance->update($request->all());
            $attendance->load(['employee', 'timesheet.project']);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Asistencia actualizada correctamente'
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
                'message' => 'Error al actualizar la asistencia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una asistencia (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        try {
            $attendance = Attendance::findOrFail($id);
            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Asistencia eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la asistencia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar una asistencia eliminada
     */
    public function restore($id): JsonResponse
    {
        try {
            $attendance = Attendance::withTrashed()->findOrFail($id);
            $attendance->restore();
            $attendance->load(['employee', 'timesheet.project']);

            return response()->json([
                'success' => true,
                'data' => $attendance,
                'message' => 'Asistencia restaurada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar la asistencia: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Registrar asistencias múltiples para un timesheet
     */
    public function bulkStore(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'timesheet_id' => 'required|exists:timesheets,id',
                'attendances' => 'required|array|min:1',
                'attendances.*.employee_id' => 'required|exists:employees,id',
                'attendances.*.status' => 'required|string|in:present,absent,late,permission,sick_leave',
                'attendances.*.shift' => 'required|string|in:morning,afternoon,night',
                'attendances.*.observation' => 'nullable|string|max:500',
            ]);

            $timesheet = Timesheet::findOrFail($request->timesheet_id);
            $createdAttendances = [];
            $errors = [];

            foreach ($request->attendances as $index => $attendanceData) {
                try {
                    // Verificar que no exista ya una asistencia para este empleado
                    $existingAttendance = Attendance::where('timesheet_id', $request->timesheet_id)
                        ->where('employee_id', $attendanceData['employee_id'])
                        ->first();

                    if ($existingAttendance) {
                        $errors[] = "El empleado con ID {$attendanceData['employee_id']} ya tiene asistencia registrada";
                        continue;
                    }

                    $attendanceData['timesheet_id'] = $request->timesheet_id;

                    // Si es presente y no tiene horarios, usar los del timesheet
                    if ($attendanceData['status'] === 'present') {
                        $attendanceData['check_in_date'] = $attendanceData['check_in_date'] ?? $timesheet->check_in_date;
                        $attendanceData['break_date'] = $attendanceData['break_date'] ?? $timesheet->break_date;
                        $attendanceData['end_break_date'] = $attendanceData['end_break_date'] ?? $timesheet->end_break_date;
                        $attendanceData['check_out_date'] = $attendanceData['check_out_date'] ?? $timesheet->check_out_date;
                    }

                    $attendance = Attendance::create($attendanceData);
                    $attendance->load(['employee']);
                    $createdAttendances[] = $attendance;

                } catch (\Exception $e) {
                    $errors[] = "Error al crear asistencia para empleado {$attendanceData['employee_id']}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => count($createdAttendances) > 0,
                'data' => $createdAttendances,
                'errors' => $errors,
                'message' => count($createdAttendances) . ' asistencias creadas correctamente'
            ], count($createdAttendances) > 0 ? 201 : 400);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar las asistencias: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de asistencias por timesheet
     */
    public function getStats($timesheetId): JsonResponse
    {
        try {
            $timesheet = Timesheet::findOrFail($timesheetId);

            $stats = [
                'total' => $timesheet->attendances()->count(),
                'present' => $timesheet->attendances()->where('status', 'present')->count(),
                'absent' => $timesheet->attendances()->where('status', 'absent')->count(),
                'late' => $timesheet->attendances()->where('status', 'late')->count(),
                'permission' => $timesheet->attendances()->where('status', 'permission')->count(),
                'sick_leave' => $timesheet->attendances()->where('status', 'sick_leave')->count(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Estadísticas obtenidas correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Búsqueda avanzada de asistencias con múltiples filtros
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'timesheet_id' => 'nullable|exists:timesheets,id',
                'project_id' => 'nullable|exists:projects,id',
                'employee_id' => 'nullable|exists:employees,id',
                'status' => 'nullable|in:present,absent,late,permission,sick_leave',
                'shift' => 'nullable|in:morning,afternoon,night',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from',
                'specific_date' => 'nullable|date',
                'employee_name' => 'nullable|string',
                'project_name' => 'nullable|string',
                'has_observation' => 'nullable|boolean'
            ]);

            $query = Attendance::with(['employee', 'timesheet.project.quote.client']);

            // Filtro por timesheet específico
            if ($request->filled('timesheet_id')) {
                $query->where('timesheet_id', $request->timesheet_id);
            }

            // Filtro por proyecto específico
            if ($request->filled('project_id')) {
                $query->whereHas('timesheet', function($q) use ($request) {
                    $q->where('project_id', $request->project_id);
                });
            }

            // Filtro por empleado específico
            if ($request->filled('employee_id')) {
                $query->where('employee_id', $request->employee_id);
            }

            // Filtro por estado
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filtro por turno
            if ($request->filled('shift')) {
                $query->where('shift', $request->shift);
            }

            // Filtro por rango de fechas
            if ($request->filled('date_from') && $request->filled('date_to')) {
                $query->whereHas('timesheet', function($q) use ($request) {
                    $q->whereBetween('check_in_date', [
                        \Carbon\Carbon::parse($request->date_from)->startOfDay(),
                        \Carbon\Carbon::parse($request->date_to)->endOfDay()
                    ]);
                });
            } elseif ($request->filled('date_from')) {
                $query->whereHas('timesheet', function($q) use ($request) {
                    $q->where('check_in_date', '>=', \Carbon\Carbon::parse($request->date_from)->startOfDay());
                });
            } elseif ($request->filled('date_to')) {
                $query->whereHas('timesheet', function($q) use ($request) {
                    $q->where('check_in_date', '<=', \Carbon\Carbon::parse($request->date_to)->endOfDay());
                });
            }

            // Filtro por fecha específica
            if ($request->filled('specific_date')) {
                $query->whereHas('timesheet', function($q) use ($request) {
                    $q->whereDate('check_in_date', \Carbon\Carbon::parse($request->specific_date));
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

            // Filtro por nombre del proyecto
            if ($request->filled('project_name')) {
                $query->whereHas('timesheet.project', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->project_name . '%');
                });
            }

            // Filtro por existencia de observaciones
            if ($request->filled('has_observation')) {
                if ($request->has_observation) {
                    $query->whereNotNull('observation');
                } else {
                    $query->whereNull('observation');
                }
            }

            $attendances = $query->orderBy('created_at', 'desc')->get();

            // Generar estadísticas de la búsqueda
            $stats = [
                'total_found' => $attendances->count(),
                'by_status' => [
                    'present' => $attendances->where('status', 'present')->count(),
                    'absent' => $attendances->where('status', 'absent')->count(),
                    'late' => $attendances->where('status', 'late')->count(),
                    'permission' => $attendances->where('status', 'permission')->count(),
                    'sick_leave' => $attendances->where('status', 'sick_leave')->count(),
                ],
                'by_shift' => $attendances->groupBy('shift')->map->count(),
                'by_project' => $attendances->groupBy('timesheet.project.name')->map->count(),
                'with_observations' => $attendances->whereNotNull('observation')->count(),
                'date_range' => [
                    'earliest' => $attendances->min('timesheet.check_in_date'),
                    'latest' => $attendances->max('timesheet.check_in_date')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $attendances,
                'statistics' => $stats,
                'message' => 'Búsqueda de asistencias completada',
                'total_found' => $attendances->count(),
                'filters_applied' => $request->only([
                    'timesheet_id', 'project_id', 'employee_id', 'status', 'shift',
                    'date_from', 'date_to', 'specific_date', 'employee_name',
                    'project_name', 'has_observation'
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
