<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkReportRequest;
use App\Http\Requests\UpdateWorkReportRequest;
use App\Models\WorkReport;
use App\Services\WorkReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class WorkReportController extends Controller
{
    /**
     * Obtener work reports con filtros avanzados y búsqueda.
     */
    public function index(Request $request): JsonResponse
    {
        $query = WorkReport::with([
            'employee.position',
            'project.subClient',
            'project.client',
            'photos'
        ]);

        // 1. Búsqueda por texto (Global)
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('description', 'like', $searchTerm)
                    // Búsqueda en relaciones
                    ->orWhereHas('employee', function ($q) use ($searchTerm) {
                        $q->where('first_name', 'like', $searchTerm)
                            ->orWhere('last_name', 'like', $searchTerm)
                            ->orWhere('document_number', 'like', $searchTerm);
                    })
                    ->orWhereHas('project', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', $searchTerm);
                    });
            });
        }

        // 2. Filtros específicos
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // 3. Filtros de fecha
        if ($request->filled('report_date')) {
            $query->whereDate('report_date', $request->report_date);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('report_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('report_date', '<=', $request->date_to);
        }

        // 4. Ordenamiento (Default: Más recientes primero)
        $query->orderBy('report_date', 'desc')
            ->orderBy('created_at', 'desc');


        // Paginación
        $workReports = $query->paginate($request->per_page ?? 15);

        // Formatear los datos
        $workReportsData = $workReports->map(function ($report) {
            return $this->formatWorkReport($report);
        });

        return response()->json([
            'success' => true,
            'message' => 'Work reports obtenidos exitosamente',
            'data' => $workReportsData,
            'pagination' => [
                'total' => $workReports->total(),
                'perPage' => $workReports->perPage(),
                'currentPage' => $workReports->currentPage(),
                'lastPage' => $workReports->lastPage(),
                'from' => $workReports->firstItem(),
                'to' => $workReports->lastItem(),
                'hasMorePages' => $workReports->hasMorePages(),
            ],
            'filters' => [
                'search' => $request->search,
                'project_id' => $request->project_id,
                'employee_id' => $request->employee_id,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ],
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Guardar un nuevo work report.
     * 💡 Optimización: Ahora usa los índices numéricos para buscar los archivos.
     */
    public function store(StoreWorkReportRequest $request, WorkReportService $workReportService): JsonResponse
    {
        $data = $request->validated();

        // Preparar los datos de las fotos con nombres estandarizados
        $photosData = [];
        $photosInput = $request->input('photos', []);
        foreach ($photosInput as $i => $photoInput) {
            $photosData[] = [
                'descripcion' => $photoInput['descripcion'] ?? '',
                'before_work_descripcion' => $photoInput['before_work_descripcion'] ?? '',
                'photo' => $request->hasFile("photos.{$i}.photo") ? $request->file("photos.{$i}.photo") : null,
                'before_work_photo' => $request->hasFile("photos.{$i}.before_work_photo") ? $request->file("photos.{$i}.before_work_photo") : null,
            ];
        }

        // Crear el WorkReport y las Photos
        // El Service recibe el array $photosData que ahora contiene los objetos UploadedFile
        $workReport = $workReportService->createWorkReportWithPhotos($data, $photosData);

        return response()->json([
            'success' => true,
            'message' => 'Work report y fotos creados exitosamente',
            'data' => $this->formatWorkReport($workReport),
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Obtener un work report específico por ID
     */
    public function show($id): JsonResponse
    {
        $workReport = WorkReport::with([
            'employee.position',
            'project.subClient',
            'project.client',
            'photos'
        ])->find($id);

        if (!$workReport) {
            return response()->json([
                'success' => false,
                'message' => 'Work report no encontrado',
                'data' => null,
                'meta' => [
                    'apiVersion' => '1.0',
                    'timestamp' => now()->utc()->toIso8601String(),
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Work report obtenido exitosamente',
            'data' => $this->formatWorkReport($workReport),
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Actualizar un work report existente.
     */
    public function update(UpdateWorkReportRequest $request, $id): JsonResponse
    {
        $workReport = WorkReport::find($id);

        if (!$workReport) {
            return response()->json([
                'success' => false,
                'message' => 'Work report no encontrado',
                'data' => null,
                'meta' => ['timestamp' => now()->utc()->toIso8601String()],
            ], 404);
        }

        $data = $request->validated();

        $workReport->update($data);

        // Recargamos relaciones
        $workReport->refresh()->load(['employee.position', 'project.subClient', 'project.client', 'photos']);

        return response()->json([
            'success' => true,
            'message' => 'Work report actualizado exitosamente',
            'data' => $this->formatWorkReport($workReport),
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Eliminar un work report.
     */
    public function destroy($id): JsonResponse
    {
        $workReport = WorkReport::find($id);

        if (!$workReport) {
            return response()->json([
                'success' => false,
                'message' => 'Work report no encontrado',
                'data' => null,
                'meta' => ['timestamp' => now()->utc()->toIso8601String()],
            ], 404);
        }

        try {
            // Ya no eliminamos archivos de firmas locales porque son Base64

            // NOTA: La eliminación de fotos asociada se debe manejar en otro controlador o en la BD.

            $workReport->delete();

            return response()->json([
                'success' => true,
                'message' => 'Work report eliminado exitosamente',
                'data' => ['id' => (int)$id],
                'meta' => [
                    'apiVersion' => '1.0',
                    'timestamp' => now()->utc()->toIso8601String(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el work report: ' . $e->getMessage(),
                'data' => null,
                'meta' => ['timestamp' => now()->utc()->toIso8601String()],
            ], 500);
        }
    }



    /**
     * Obtener work reports por proyecto
     */
    public function getByProject($projectId): JsonResponse
    {
        $workReports = WorkReport::with([
            'employee.position',
            'project.subClient',
            'project.client',
            'photos'
        ])
            ->where('project_id', $projectId)
            ->orderBy('report_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($workReports->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No se encontraron work reports para este proyecto',
                'data' => [],
                'meta' => [
                    'apiVersion' => '1.0',
                    'timestamp' => now()->utc()->toIso8601String(),
                ],
            ], 200);
        }

        $data = $workReports->map(function ($report) {
            return $this->formatWorkReport($report);
        });

        return response()->json([
            'success' => true,
            'message' => 'Work reports del proyecto obtenidos exitosamente',
            'data' => $data->values()->toArray(),
            'summary' => [
                'totalReports' => $workReports->count(),
                'project_id' => (int) $projectId,
            ],
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Obtener work reports por empleado
     */
    public function getByEmployee($employeeId): JsonResponse
    {
        $workReports = WorkReport::with([
            'employee.position',
            'project.subClient',
            'project.client',
            'photos'
        ])
            ->where('employee_id', $employeeId)
            ->orderBy('report_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($workReports->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No se encontraron work reports para este empleado',
                'data' => [],
                'meta' => [
                    'apiVersion' => '1.0',
                    'timestamp' => now()->utc()->toIso8601String(),
                ],
            ], 200);
        }

        $data = $workReports->map(function ($report) {
            return $this->formatWorkReport($report);
        });

        return response()->json([
            'success' => true,
            'message' => 'Work reports del empleado obtenidos exitosamente',
            'data' => $data->values()->toArray(),
            'summary' => [
                'totalReports' => $workReports->count(),
                'employee_id' => (int) $employeeId,
            ],
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    private function formatWorkReport($report)
    {
        return [
            'id' => $report->id,
            'employee_id' => $report->employee_id,
            'project_id' => $report->project_id,
            'name' => $report->name ?? '',
            'description' => $report->description ?? '',
            'supervisor_signature' => $report->supervisor_signature ? url(Storage::url($report->supervisor_signature)) : null,
            'manager_signature' => $report->manager_signature ? url(Storage::url($report->manager_signature)) : null,
            'suggestions' => $report->suggestions ?? '',
            'tools' => $report->tools ?? '',
            'personnel' => $report->personnel ?? '',
            'materials' => $report->materials ?? '',
            'start_time' => $report->start_time ?? null,
            'end_time' => $report->end_time ?? null,
            'report_date' => $report->report_date ?? null,
            'created_at' => $report->created_at?->toIso8601String(),
            'updated_at' => $report->updated_at?->toIso8601String(),
            'summary' => [
                'hasPhotos' => $report->photos->isNotEmpty(),
                'photosCount' => $report->photos->count(),
                'hasSignatures' => !is_null($report->supervisor_signature) || !is_null($report->manager_signature),
            ],
            'employee' => $report->employee ? [
                'id' => $report->employee->id,
                'document_type' => $report->employee->document_type ?? '',
                'document_number' => $report->employee->document_number ?? '',
                'first_name' => $report->employee->first_name ?? '',
                'last_name' => $report->employee->last_name ?? '',
                'full_name' => trim(($report->employee->first_name ?? '') . ' ' . ($report->employee->last_name ?? '')),
                'position' => $report->employee->position ? [
                    'id' => $report->employee->position->id,
                    'name' => $report->employee->position->name ?? '',
                ] : null,
            ] : null,
            'project' => $report->project ? [
                'id' => $report->project->id,
                'name' => $report->project->name ?? '',
                'location_latitude' => $report->project->location_latitude ?? null,
                'location_longitude' => $report->project->location_longitude ?? null,
                'coordinates' => $report->project->coordinates ?? '',
                'start_date' => $report->project->start_date?->toDateString(),
                'end_date' => $report->project->end_date?->toDateString(),
                'status_text' => $report->project->status_text ?? 'Sin definir',
                'subClient' => $report->project->subClient ? [
                    'id' => $report->project->subClient->id,
                    'name' => $report->project->subClient->name ?? '',
                ] : null,
                'client' => $report->project->client ? [
                    'id' => $report->project->client->id,
                    'name' => $report->project->client->name ?? '',
                ] : null,
            ] : null,
            'photos' => $report->photos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'work_report_id' => $photo->work_report_id,
                    'photo_path' => $photo->photo_path ? url(Storage::url($photo->photo_path)) : null,
                    'descripcion' => $photo->descripcion ?? '',
                    'before_work_photo_path' => $photo->before_work_photo_path ? url(Storage::url($photo->before_work_photo_path)) : null,
                    'before_work_descripcion' => $photo->before_work_descripcion ?? '',
                    'created_at' => $photo->created_at?->toIso8601String(),
                    'updated_at' => $photo->updated_at?->toIso8601String(),
                ];
            })->values()->toArray(),
        ];
    }
}