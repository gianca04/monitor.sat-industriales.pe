<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\WorkReportConsolidatedService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkReportConsolidatedController extends Controller
{
    public function __construct(
        private WorkReportConsolidatedService $consolidatedService
    ) {}

    /**
     * Genera un reporte consolidado de todos los reportes de trabajo de un proyecto
     *
     * @param int $projectId
     * @param Request $request
     * @return BinaryFileResponse|Response
     */
    public function generateConsolidatedReport(int $projectId, Request $request)
    {
        try {
            // Validar parámetros de la request
            $request->validate([
                'inline' => 'boolean'
            ]);

            // Verificar que el proyecto existe
            $project = Project::findOrFail($projectId);
            
            // Verificar que el proyecto tiene reportes de trabajo
            $workReportsCount = $project->workReports()->count();
            if ($workReportsCount === 0) {
                return response()->json([
                    'error' => 'El proyecto no tiene reportes de trabajo',
                    'message' => 'No se pueden generar reportes consolidados para proyectos sin reportes de trabajo'
                ], 400);
            }

            // Generar PDF consolidado
            $pdf = $this->consolidatedService->generateConsolidatedPdf($projectId);
            $filename = $this->consolidatedService->generateConsolidatedFilename($project);
            
            // Determinar disposición
            $disposition = $request->boolean('inline') ? 'inline' : 'attachment';
            
            Log::info('PDF consolidado generado exitosamente', [
                'project_id' => $projectId,
                'project_name' => $project->name,
                'work_reports_count' => $workReportsCount,
                'filename' => $filename,
                'disposition' => $disposition
            ]);
            
            return $pdf->stream($filename, ['Content-Disposition' => $disposition]);
            
        } catch (\Exception $e) {
            Log::error('Error generando PDF consolidado', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al generar el reporte consolidado',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtiene las estadísticas de un reporte consolidado
     *
     * @param int $projectId
     * @return JsonResponse
     */
    public function getConsolidatedStatistics(int $projectId): JsonResponse
    {
        try {
            // Verificar que el proyecto existe
            Project::findOrFail($projectId);
            
            $statistics = $this->consolidatedService->getConsolidatedStatistics($projectId);
            
            return response()->json([
                'project_id' => $projectId,
                'statistics' => $statistics
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas consolidadas', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error obteniendo estadísticas consolidadas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Previsualiza la información del reporte consolidado sin generar el PDF
     *
     * @param int $projectId
     * @return JsonResponse
     */
    public function previewConsolidatedReport(int $projectId): JsonResponse
    {
        try {
            // Verificar que el proyecto existe
            $project = Project::findOrFail($projectId);
            
            // Obtener reportes de trabajo
            $workReports = $this->consolidatedService->getProjectWorkReports($projectId);
            
            if ($workReports->isEmpty()) {
                return response()->json([
                    'error' => 'El proyecto no tiene reportes de trabajo',
                    'message' => 'No se pueden generar reportes consolidados para proyectos sin reportes de trabajo'
                ], 400);
            }
            
            // Preparar datos de previsualización
            $preview = [
                'project' => [
                    'id' => $project->id,
                    'name' => $project->name,
                    'client' => $project->subClient->client->business_name ?? 'N/A',
                    'subclient' => $project->subClient->name ?? 'N/A'
                ],
                'reports_summary' => $workReports->map(function ($report) {
                    return [
                        'id' => $report->id,
                        'name' => $report->name,
                        'date' => $report->report_date,
                        'start_time' => $report->start_time,
                        'end_time' => $report->end_time,
                        'employee' => $report->employee->full_name ?? 'N/A',
                        'photos_count' => $report->photos->count()
                    ];
                }),
                'statistics' => $this->consolidatedService->getConsolidatedStatistics($projectId),
                'estimated_filename' => $this->consolidatedService->generateConsolidatedFilename($project)
            ];
            
            return response()->json($preview);
            
        } catch (\Exception $e) {
            Log::error('Error previsualizando reporte consolidado', [
                'project_id' => $projectId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error previsualizando reporte consolidado',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}