<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ReportController extends Controller
{
    /**
     * Obtener reporte de asistencias por proyecto y rango de fechas
     */
    public function attendanceReport(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'project_id' => 'nullable|exists:projects,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'status' => 'nullable|string|in:present,absent,late,permission,sick_leave'
            ]);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

            $query = Attendance::with(['employee', 'timesheet.project'])
                ->whereHas('timesheet', function($q) use ($startDate, $endDate, $request) {
                    $q->whereBetween('check_in_date', [$startDate, $endDate]);

                    if ($request->has('project_id')) {
                        $q->where('project_id', $request->project_id);
                    }
                });

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $attendances = $query->orderBy('created_at', 'desc')->get();

            // Generar estadísticas
            $stats = [
                'total_attendances' => $attendances->count(),
                'by_status' => [
                    'present' => $attendances->where('status', 'present')->count(),
                    'absent' => $attendances->where('status', 'absent')->count(),
                    'late' => $attendances->where('status', 'late')->count(),
                    'permission' => $attendances->where('status', 'permission')->count(),
                    'sick_leave' => $attendances->where('status', 'sick_leave')->count(),
                ],
                'by_project' => $attendances->groupBy('timesheet.project.name')->map->count(),
                'by_employee' => $attendances->groupBy(function($attendance) {
                    return $attendance->employee->first_name . ' ' . $attendance->employee->last_name;
                })->map->count()
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'attendances' => $attendances,
                    'statistics' => $stats,
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString()
                    ]
                ],
                'message' => 'Reporte de asistencias generado correctamente'
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
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas generales del dashboard
     */
    public function dashboard(): JsonResponse
    {
        try {
            $today = Carbon::today();
            $currentWeekStart = Carbon::now()->startOfWeek();
            $currentWeekEnd = Carbon::now()->endOfWeek();
            $currentMonthStart = Carbon::now()->startOfMonth();
            $currentMonthEnd = Carbon::now()->endOfMonth();

            // Proyectos activos
            $activeProjects = Project::where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->count();

            // Timesheets de hoy
            $todayTimesheets = Timesheet::whereDate('check_in_date', $today)->count();

            // Asistencias de hoy
            $todayAttendances = Attendance::whereHas('timesheet', function($q) use ($today) {
                $q->whereDate('check_in_date', $today);
            })->count();

            // Estadísticas de asistencia de la semana actual
            $weeklyAttendances = Attendance::whereHas('timesheet', function($q) use ($currentWeekStart, $currentWeekEnd) {
                $q->whereBetween('check_in_date', [$currentWeekStart, $currentWeekEnd]);
            })->get();

            $weeklyStats = [
                'total' => $weeklyAttendances->count(),
                'present' => $weeklyAttendances->where('status', 'present')->count(),
                'absent' => $weeklyAttendances->where('status', 'absent')->count(),
                'late' => $weeklyAttendances->where('status', 'late')->count(),
                'permission' => $weeklyAttendances->where('status', 'permission')->count(),
                'sick_leave' => $weeklyAttendances->where('status', 'sick_leave')->count(),
            ];

            // Estadísticas de asistencia del mes actual
            $monthlyAttendances = Attendance::whereHas('timesheet', function($q) use ($currentMonthStart, $currentMonthEnd) {
                $q->whereBetween('check_in_date', [$currentMonthStart, $currentMonthEnd]);
            })->get();

            $monthlyStats = [
                'total' => $monthlyAttendances->count(),
                'present' => $monthlyAttendances->where('status', 'present')->count(),
                'absent' => $monthlyAttendances->where('status', 'absent')->count(),
                'late' => $monthlyAttendances->where('status', 'late')->count(),
                'permission' => $monthlyAttendances->where('status', 'permission')->count(),
                'sick_leave' => $monthlyAttendances->where('status', 'sick_leave')->count(),
            ];

            // Proyectos más activos (con más timesheets este mes)
            $topProjects = Project::withCount(['timesheets' => function($query) use ($currentMonthStart, $currentMonthEnd) {
                $query->whereBetween('check_in_date', [$currentMonthStart, $currentMonthEnd]);
            }])
            ->orderByDesc('timesheets_count')
            ->limit(5)
            ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'summary' => [
                        'active_projects' => $activeProjects,
                        'today_timesheets' => $todayTimesheets,
                        'today_attendances' => $todayAttendances,
                        'total_employees' => Employee::count()
                    ],
                    'weekly_stats' => $weeklyStats,
                    'monthly_stats' => $monthlyStats,
                    'top_projects' => $topProjects,
                    'date_info' => [
                        'today' => $today->toDateString(),
                        'week_range' => [
                            'start' => $currentWeekStart->toDateString(),
                            'end' => $currentWeekEnd->toDateString()
                        ],
                        'month_range' => [
                            'start' => $currentMonthStart->toDateString(),
                            'end' => $currentMonthEnd->toDateString()
                        ]
                    ]
                ],
                'message' => 'Estadísticas del dashboard obtenidas correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reporte de productividad por empleado
     */
    public function employeeProductivity(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'employee_id' => 'nullable|exists:employees,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $endDate = Carbon::parse($request->end_date)->endOfDay();

            $query = Employee::with(['attendances.timesheet.project'])
                ->withCount(['attendances as total_attendances' => function($q) use ($startDate, $endDate) {
                    $q->whereHas('timesheet', function($tq) use ($startDate, $endDate) {
                        $tq->whereBetween('check_in_date', [$startDate, $endDate]);
                    });
                }])
                ->withCount(['attendances as present_count' => function($q) use ($startDate, $endDate) {
                    $q->where('status', 'present')
                      ->whereHas('timesheet', function($tq) use ($startDate, $endDate) {
                          $tq->whereBetween('check_in_date', [$startDate, $endDate]);
                      });
                }])
                ->withCount(['attendances as absent_count' => function($q) use ($startDate, $endDate) {
                    $q->where('status', 'absent')
                      ->whereHas('timesheet', function($tq) use ($startDate, $endDate) {
                          $tq->whereBetween('check_in_date', [$startDate, $endDate]);
                      });
                }])
                ->withCount(['attendances as late_count' => function($q) use ($startDate, $endDate) {
                    $q->where('status', 'late')
                      ->whereHas('timesheet', function($tq) use ($startDate, $endDate) {
                          $tq->whereBetween('check_in_date', [$startDate, $endDate]);
                      });
                }]);

            if ($request->has('employee_id')) {
                $query->where('id', $request->employee_id);
            }

            $employees = $query->get()->map(function($employee) {
                $attendanceRate = $employee->total_attendances > 0
                    ? round(($employee->present_count / $employee->total_attendances) * 100, 2)
                    : 0;

                return [
                    'employee' => $employee,
                    'stats' => [
                        'total_attendances' => $employee->total_attendances,
                        'present_count' => $employee->present_count,
                        'absent_count' => $employee->absent_count,
                        'late_count' => $employee->late_count,
                        'attendance_rate' => $attendanceRate
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'employees' => $employees,
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString()
                    ]
                ],
                'message' => 'Reporte de productividad generado correctamente'
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
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener reporte de timesheets por proyecto
     */
    public function projectTimesheets(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            $query = Timesheet::with(['employee', 'attendances.employee'])
                ->where('project_id', $request->project_id);

            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = Carbon::parse($request->start_date)->startOfDay();
                $endDate = Carbon::parse($request->end_date)->endOfDay();
                $query->whereBetween('check_in_date', [$startDate, $endDate]);
            }

            $timesheets = $query->orderBy('check_in_date', 'desc')->get();

            $project = Project::with('quote.client')->findOrFail($request->project_id);

            // Estadísticas del proyecto
            $totalTimesheets = $timesheets->count();
            $totalAttendances = $timesheets->sum(function($timesheet) {
                return $timesheet->attendances->count();
            });

            $attendancesByStatus = [];
            foreach (['present', 'absent', 'late', 'permission', 'sick_leave'] as $status) {
                $attendancesByStatus[$status] = $timesheets->sum(function($timesheet) use ($status) {
                    return $timesheet->attendances->where('status', $status)->count();
                });
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'project' => $project,
                    'timesheets' => $timesheets,
                    'statistics' => [
                        'total_timesheets' => $totalTimesheets,
                        'total_attendances' => $totalAttendances,
                        'attendances_by_status' => $attendancesByStatus
                    ]
                ],
                'message' => 'Reporte de timesheets del proyecto generado correctamente'
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
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }
}
