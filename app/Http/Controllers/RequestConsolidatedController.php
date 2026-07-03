<?php

namespace App\Http\Controllers;

use App\Models\Request;
use App\Services\RequestConsolidatedService;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RequestConsolidatedController extends Controller
{
    public function __construct(
        private RequestConsolidatedService $consolidatedService
    ) {}

    /**
     * Genera un reporte consolidado de todas las visitas de un request
     *
     * @param int $requestId
     * @param HttpRequest $request
     * @return BinaryFileResponse|Response|JsonResponse
     */
    public function generateConsolidatedReport(int $requestId, HttpRequest $request)
    {
        try {
            // Validar parámetros de la request
            $request->validate([
                'inline' => 'boolean'
            ]);

            // Verificar que el request existe
            $requestModel = Request::findOrFail($requestId);

            // Verificar que el request tiene visitas
            $visitsCount = $requestModel->visitas()->count();
            if ($visitsCount === 0) {
                return response()->json([
                    'error' => 'El request no tiene visitas',
                    'message' => 'No se pueden generar reportes consolidados para requests sin visitas'
                ], 400);
            }

            // Generar PDF consolidado
            $pdf = $this->consolidatedService->generateConsolidatedPdf($requestId);
            $filename = $this->consolidatedService->generateConsolidatedFilename($requestModel);

            // Determinar si usar inline o attachment
            if ($request->boolean('inline')) {
                return $pdf->stream($filename);
            } else {
                return $pdf->download($filename);
            }        } catch (\Exception $e) {
            Log::error('Error generando PDF consolidado de request', [
                'request_id' => $requestId,
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
     * @param int $requestId
     * @return JsonResponse
     */
    public function getConsolidatedStatistics(int $requestId): JsonResponse
    {
        try {
            // Verificar que el request existe
            Request::findOrFail($requestId);

            $statistics = $this->consolidatedService->getConsolidatedStatistics($requestId);

            return response()->json([
                'request_id' => $requestId,
                'statistics' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas consolidadas de request', [
                'request_id' => $requestId,
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
     * @param int $requestId
     * @return JsonResponse
     */
    public function previewConsolidatedReport(int $requestId): JsonResponse
    {
        try {
            // Verificar que el request existe
            $requestModel = Request::findOrFail($requestId);

            // Obtener visitas
            $visits = $this->consolidatedService->getRequestVisits($requestId);

            if ($visits->isEmpty()) {
                return response()->json([
                    'error' => 'El request no tiene visitas',
                    'message' => 'No se pueden generar reportes consolidados para requests sin visitas'
                ], 400);
            }

            // Preparar datos de previsualización
            $preview = [
                'request' => [
                    'id' => $requestModel->id,
                    'reference' => $requestModel->reference,
                    'description' => $requestModel->description,
                    'visit_date' => $requestModel->visit_date,
                    'status' => $requestModel->status,
                    'client' => $requestModel->subClient->client->business_name ?? 'N/A',
                    'subclient' => $requestModel->subClient->name ?? 'N/A',
                    'cotizador' => $requestModel->cotizador->full_name ?? 'N/A',
                    'supervisor' => $requestModel->supervisor->full_name ?? 'N/A'
                ],
                'visits_summary' => $visits->map(function ($visit) {
                    return [
                        'id' => $visit->id,
                        'name' => $visit->name,
                        'date' => $visit->report_date,
                        'start_time' => $visit->start_time,
                        'end_time' => $visit->end_time,
                        'employee' => $visit->employee->full_name ?? 'N/A',
                        'photos_count' => $visit->visitPhotos->count(),
                        'description' => $visit->description
                    ];
                }),
                'statistics' => $this->consolidatedService->getConsolidatedStatistics($requestId),
                'estimated_filename' => $this->consolidatedService->generateConsolidatedFilename($requestModel)
            ];

            return response()->json($preview);

        } catch (\Exception $e) {
            Log::error('Error previsualizando reporte consolidado de request', [
                'request_id' => $requestId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error previsualizando reporte consolidado',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}