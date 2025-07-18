<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    /**
     * Obtener todos los empleados
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Employee::query();

            // Filtrar por tipo de documento si se especifica
            if ($request->has('document_type')) {
                $query->where('document_type', $request->document_type);
            }

            // Buscar por nombre o número de documento
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('document_number', 'like', "%{$search}%");
                });
            }

            $employees = $query->orderBy('first_name')->orderBy('last_name')->get();

            return response()->json([
                'data' => $employees,
                'message' => 'Empleados obtenidos correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los empleados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un empleado específico
     */
    public function show($id): JsonResponse
    {
        try {
            $employee = Employee::with(['attendances.timesheet.project', 'timesheets.project'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $employee,
                'message' => 'Empleado obtenido correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado'
            ], 404);
        }
    }

    /**
     * Obtener empleados disponibles para un proyecto en una fecha específica
     */
    public function getAvailableForProject(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'project_id' => 'required|exists:projects,id',
                'date' => 'required|date'
            ]);

            // Obtener todos los empleados
            $allEmployees = Employee::orderBy('first_name')->orderBy('last_name')->get();

            // Obtener empleados que ya tienen asistencia registrada en este proyecto y fecha
            $employeesWithAttendance = Employee::whereHas('attendances', function($query) use ($request) {
                $query->whereHas('timesheet', function($q) use ($request) {
                    $q->where('project_id', $request->project_id)
                      ->whereDate('check_in_date', $request->date);
                });
            })->pluck('id');

            // Marcar empleados con asistencia ya registrada
            $employees = $allEmployees->map(function($employee) use ($employeesWithAttendance) {
                $employee->has_attendance = $employeesWithAttendance->contains($employee->id);
                return $employee;
            });

            return response()->json([
                'success' => true,
                'data' => $employees,
                'message' => 'Empleados obtenidos correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los empleados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Búsqueda avanzada de empleados con múltiples filtros
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'nullable|string',
                'document_type' => 'nullable|string',
                'document_number' => 'nullable|string',
                'sex' => 'nullable|in:M,F',
                'age_min' => 'nullable|integer|min:18',
                'age_max' => 'nullable|integer|max:100',
                'contract_date_from' => 'nullable|date',
                'contract_date_to' => 'nullable|date|after_or_equal:contract_date_from',
                'address' => 'nullable|string',
                'has_attendances' => 'nullable|boolean',
                'project_id' => 'nullable|exists:projects,id',
                'attendance_period_from' => 'nullable|date',
                'attendance_period_to' => 'nullable|date|after_or_equal:attendance_period_from'
            ]);

            $query = Employee::with(['attendances.timesheet.project', 'timesheets.project']);

            // Filtro por nombre completo
            if ($request->filled('name')) {
                $name = $request->name;
                $query->where(function($q) use ($name) {
                    $q->where('first_name', 'like', "%{$name}%")
                      ->orWhere('last_name', 'like', "%{$name}%")
                      ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$name}%"]);
                });
            }

            // Filtro por tipo de documento
            if ($request->filled('document_type')) {
                $query->where('document_type', $request->document_type);
            }

            // Filtro por número de documento
            if ($request->filled('document_number')) {
                $query->where('document_number', 'like', '%' . $request->document_number . '%');
            }

            // Filtro por sexo
            if ($request->filled('sex')) {
                $query->where('sex', $request->sex);
            }

            // Filtro por edad
            if ($request->filled('age_min')) {
                $birthDateMax = Carbon::now()->subYears($request->age_min)->endOfYear();
                $query->where('date_birth', '<=', $birthDateMax);
            }
            if ($request->filled('age_max')) {
                $birthDateMin = Carbon::now()->subYears($request->age_max + 1)->startOfYear();
                $query->where('date_birth', '>=', $birthDateMin);
            }

            // Filtro por rango de fecha de contrato
            if ($request->filled('contract_date_from')) {
                $query->where('date_contract', '>=', Carbon::parse($request->contract_date_from));
            }
            if ($request->filled('contract_date_to')) {
                $query->where('date_contract', '<=', Carbon::parse($request->contract_date_to));
            }

            // Filtro por dirección
            if ($request->filled('address')) {
                $query->where('address', 'like', '%' . $request->address . '%');
            }

            // Filtro por existencia de asistencias
            if ($request->filled('has_attendances')) {
                if ($request->has_attendances) {
                    $query->has('attendances');
                } else {
                    $query->doesntHave('attendances');
                }
            }

            // Filtro por proyecto específico
            if ($request->filled('project_id')) {
                $query->whereHas('attendances.timesheet', function($q) use ($request) {
                    $q->where('project_id', $request->project_id);
                });
            }

            // Filtro por período de asistencias
            if ($request->filled('attendance_period_from') || $request->filled('attendance_period_to')) {
                $query->whereHas('attendances.timesheet', function($q) use ($request) {
                    if ($request->filled('attendance_period_from')) {
                        $q->where('check_in_date', '>=', Carbon::parse($request->attendance_period_from)->startOfDay());
                    }
                    if ($request->filled('attendance_period_to')) {
                        $q->where('check_in_date', '<=', Carbon::parse($request->attendance_period_to)->endOfDay());
                    }
                });
            }

            $employees = $query->orderBy('first_name')->orderBy('last_name')->get();

            // Agregar estadísticas calculadas
            $employees = $employees->map(function($employee) use ($request) {
                // Agregar información adicional del empleado
                $employee->full_name = trim($employee->first_name . ' ' . $employee->last_name);
                $employee->age = null; // Por ahora, dejaremos esto sin calcular

                // Calcular estadísticas de asistencia
                $attendancesQuery = $employee->attendances();

                if ($request->filled('attendance_period_from') || $request->filled('attendance_period_to')) {
                    $attendancesQuery->whereHas('timesheet', function($q) use ($request) {
                        if ($request->filled('attendance_period_from')) {
                            $q->where('check_in_date', '>=', Carbon::parse($request->attendance_period_from)->startOfDay());
                        }
                        if ($request->filled('attendance_period_to')) {
                            $q->where('check_in_date', '<=', Carbon::parse($request->attendance_period_to)->endOfDay());
                        }
                    });
                }

                $attendances = $attendancesQuery->get();

                $employee->attendance_stats = [
                    'total' => $attendances->count(),
                    'present' => $attendances->where('status', 'present')->count(),
                    'absent' => $attendances->where('status', 'absent')->count(),
                    'late' => $attendances->where('status', 'late')->count(),
                    'permission' => $attendances->where('status', 'permission')->count(),
                    'sick_leave' => $attendances->where('status', 'sick_leave')->count(),
                ];

                return $employee;
            });

            return response()->json([
                'success' => true,
                'data' => $employees,
                'message' => 'Búsqueda de empleados completada',
                'total_found' => $employees->count(),
                'filters_applied' => $request->only([
                    'name', 'document_type', 'document_number', 'sex', 'age_min', 'age_max',
                    'contract_date_from', 'contract_date_to', 'address', 'has_attendances',
                    'project_id', 'attendance_period_from', 'attendance_period_to'
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

    /**
     * Crear un nuevo empleado
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'document_type' => 'required|string|in:DNI,PASAPORTE,CARNET DE EXTRANJERIA',
                'document_number' => 'required|string|max:12|unique:employees',
                'first_name' => 'required|string|max:40',
                'last_name' => 'required|string|max:40',
                'address' => 'required|string|max:40',
                'date_contract' => 'required|date',
                'date_birth' => 'required|date|before:today',
                'sex' => 'required|string|in:male,female,other',
            ]);

            $employee = Employee::create($request->all());

            return response()->json([
                'success' => true,
                'data' => $employee,
                'message' => 'Empleado creado correctamente'
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el empleado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un empleado
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $employee = Employee::findOrFail($id);

            $request->validate([
                'document_type' => 'sometimes|string|in:DNI,PASAPORTE,CARNET DE EXTRANJERIA',
                'document_number' => 'sometimes|string|max:12|unique:employees,document_number,' . $id,
                'first_name' => 'sometimes|string|max:40',
                'last_name' => 'sometimes|string|max:40',
                'address' => 'sometimes|string|max:40',
                'date_contract' => 'sometimes|date',
                'date_birth' => 'sometimes|date|before:today',
                'sex' => 'sometimes|string|in:male,female,other',
            ]);

            $employee->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $employee,
                'message' => 'Empleado actualizado correctamente'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado'
            ], 404);
        }
    }

    /**
     * Eliminar un empleado
     */
    public function destroy($id): JsonResponse
    {
        try {
            $employee = Employee::findOrFail($id);
            $employee->delete();

            return response()->json([
                'success' => true,
                'message' => 'Empleado eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Empleado no encontrado'
            ], 404);
        }
    }
}
