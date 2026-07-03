<?php

namespace App\Http\Controllers;

use App\Models\WorkReport;
use App\Services\WorkReportPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
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
     * @return Response|JsonResponse
     */
    public function generateReport(int $workReportId, Request $request)
    {
        try {
            // Validar parámetros de la request
            $request->validate([
                'inline' => 'boolean',
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
     * Genera PDF de forma síncrona (siempre en tiempo real)
     */
    private function generateSync(int $workReportId, Request $request): Response
    {
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
        
        if ($disposition === 'inline') {
            return $pdf->stream($filename);
        } else {
            return $pdf->download($filename);
        }
    }

    /**
     * Inicia generación asíncrona
     */
    private function generateAsync(int $workReportId, Request $request): JsonResponse
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
     * Obtiene el estado de un PDF (ya no hay caché, siempre se genera en tiempo real)
     *
     * @param int $workReportId
     * @return Response
     */
    
    /**
     * Fuerza la regeneración de un PDF (ya no aplica, siempre se regenera)
     *
     * @param int $workReportId
     * @return JsonResponse
     */
    public function regeneratePdf(int $workReportId): JsonResponse
    {
        try {
            // Generar PDF en tiempo real
            $pdf = $this->pdfService->generateSync($workReportId);
            $workReport = $this->pdfService->getWorkReportWithRelations($workReportId);
            $filename = $this->pdfService->generateFilename($workReport);

            return response()->json([
                'message' => 'PDF generado exitosamente',
                'work_report_id' => $workReportId,
                'filename' => $filename
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generando PDF', [
                'work_report_id' => $workReportId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error al generar el PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
