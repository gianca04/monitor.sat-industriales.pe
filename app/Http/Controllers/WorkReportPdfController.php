<?php

namespace App\Http\Controllers;

use App\Models\WorkReport;
use App\Services\WorkReportPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WorkReportPdfController extends Controller
{
    public function __construct(
        private WorkReportPdfService $pdfService
    ) {}

    /**
     * Genera un reporte de trabajo en formato PDF
     *
     * @param int $workReportId
     * @param Request $request
     * @return BinaryFileResponse|Response
     */
    public function generateReport(int $workReportId, Request $request)
    {
        try {
            // Validar parámetros de la request
            $request->validate([
                'inline' => 'boolean',
                'force_regenerate' => 'boolean',
                'async' => 'boolean',
                'email' => 'email|nullable'
            ]);

            // Si se solicita generación asíncrona
            if ($request->boolean('async')) {
                return $this->generateAsync($workReportId, $request);
            }

            // Generación síncrona
            return $this->generateSync($workReportId, $request);
            
        } catch (\Exception $e) {
            Log::error('Error generando PDF de reporte de trabajo', [
                'work_report_id' => $workReportId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Error al generar el reporte PDF',
                'message' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Genera PDF de forma síncrona
     */
    private function generateSync(int $workReportId, Request $request)
    {
        $forceRegenerate = $request->boolean('force_regenerate', false);
        
        // Intentar obtener desde caché primero
        if (!$forceRegenerate) {
            $cachedPath = $this->pdfService->getCachedOrGenerate($workReportId);
            if ($cachedPath) {
                return $this->streamStoredPdf($cachedPath, $workReportId, $request);
            }
        }

        // Generar PDF en tiempo real
        $pdf = $this->pdfService->generateSync($workReportId);
        $workReport = $this->pdfService->getWorkReportWithRelations($workReportId);
        $filename = $this->pdfService->generateFilename($workReport);
        
        // Determinar disposición
        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';
        
        Log::info('PDF generado sincrónicamente', [
            'work_report_id' => $workReportId,
            'filename' => $filename,
            'disposition' => $disposition
        ]);
        
        return $pdf->stream($filename, ['Content-Disposition' => $disposition]);
    }

    /**
     * Inicia generación asíncrona
     */
    private function generateAsync(int $workReportId, Request $request)
    {
        $userEmail = $request->input('email');
        $shouldEmail = !empty($userEmail);
        
        $this->pdfService->generateAsync($workReportId, $userEmail, $shouldEmail);
        
        Log::info('Generación asíncrona de PDF iniciada', [
            'work_report_id' => $workReportId,
            'should_email' => $shouldEmail,
            'email' => $userEmail
        ]);
        
        return response()->json([
            'message' => 'Generación de PDF iniciada',
            'work_report_id' => $workReportId,
            'async' => true,
            'email_notification' => $shouldEmail
        ], 202);
    }

    /**
     * Stream de PDF almacenado
     */
    private function streamStoredPdf(string $path, int $workReportId, Request $request)
    {
        $fullPath = storage_path('app/public/' . $path);
        
        if (!file_exists($fullPath)) {
            throw new \Exception('Archivo PDF no encontrado');
        }
        
        $workReport = $this->pdfService->getWorkReportWithRelations($workReportId);
        $filename = $this->pdfService->generateFilename($workReport);
        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';
        
        Log::info('PDF servido desde almacenamiento', [
            'work_report_id' => $workReportId,
            'path' => $path,
            'disposition' => $disposition
        ]);
        
        return response()->file($fullPath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "{$disposition}; filename=\"{$filename}\""
        ]);
    }

    /**
     * Obtiene el estado de un PDF (si existe en caché/storage)
     *
     * @param int $workReportId
     * @return Response
     */
    public function getPdfStatus(int $workReportId): Response
    {
        try {
            $cachedPath = $this->pdfService->getCachedOrGenerate($workReportId, false);
            
            return response()->json([
                'work_report_id' => $workReportId,
                'exists' => !is_null($cachedPath),
                'path' => $cachedPath,
                'last_generated' => $cachedPath ? \Storage::disk('public')->lastModified($cachedPath) : null
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'work_report_id' => $workReportId,
                'exists' => false,
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Fuerza la regeneración de un PDF
     *
     * @param int $workReportId
     * @return Response
     */
    public function regeneratePdf(int $workReportId): Response
    {
        try {
            $path = $this->pdfService->getCachedOrGenerate($workReportId, true);
            
            return response()->json([
                'message' => 'PDF regenerado exitosamente',
                'work_report_id' => $workReportId,
                'path' => $path
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error regenerando PDF', [
                'work_report_id' => $workReportId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error al regenerar el PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de PDFs
     *
     * @return Response
     */
    public function getStatistics(): Response
    {
        try {
            $stats = $this->pdfService->getStatistics();
            
            return response()->json([
                'statistics' => $stats
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error obteniendo estadísticas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpia PDFs antiguos
     *
     * @param Request $request
     * @return Response
     */
    public function cleanupOldPdfs(Request $request): Response
    {
        $request->validate([
            'days_old' => 'integer|min:1|max:365'
        ]);
        
        try {
            $daysOld = $request->integer('days_old', 30);
            $deletedCount = $this->pdfService->cleanupOldPdfs($daysOld);
            
            return response()->json([
                'message' => 'Limpieza completada',
                'deleted_files' => $deletedCount,
                'days_old' => $daysOld
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error durante la limpieza',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
