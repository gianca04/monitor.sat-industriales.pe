<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Services\VisitReportPdfService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VisitReportPdfController extends Controller
{
    public function __construct(
        private VisitReportPdfService $pdfService
    ) {}

    /**
     * Genera un reporte de visita en formato PDF
     *
     * @param int $visitId
     * @param Request $request
     * @return Response|JsonResponse
     */
    public function generateReport(int $visitId, Request $request)
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
                return $this->generateAsync($visitId, $request);
            }

            // Generación síncrona
            return $this->generateSync($visitId, $request);
            
        } catch (\Exception $e) {
            Log::error('Error generando PDF de reporte de trabajo', [
                'visit_id' => $visitId,
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
    private function generateSync(int $visitId, Request $request): Response
    {
        // Generar PDF en tiempo real
        $pdf = $this->pdfService->generateSync($visitId);
        $visit = $this->pdfService->getVisitWithRelations($visitId);
        $filename = $this->pdfService->generateFilename($visit);
        
        // Determinar disposición
        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';
        
        Log::info('PDF generado sincrónicamente', [
            'visit_id' => $visitId,
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
    private function generateAsync(int $visitId, Request $request): JsonResponse
    {
        $userEmail = $request->input('email');
        $shouldEmail = !empty($userEmail);
        
        $this->pdfService->generateAsync($visitId, $userEmail, $shouldEmail);
        
        Log::info('Generación asíncrona de PDF iniciada', [
            'visit_id' => $visitId,
            'should_email' => $shouldEmail,
            'email' => $userEmail
        ]);
        
        return response()->json([
            'message' => 'Generación de PDF iniciada',
            'visit_id' => $visitId,
            'async' => true,
            'email_notification' => $shouldEmail
        ], 202);
    }

    /**
     * Obtiene el estado de un PDF (ya no hay caché, siempre se genera en tiempo real)
     *
     * @param int $visitId
     * @return Response
     */
    
    /**
     * Fuerza la regeneración de un PDF (ya no aplica, siempre se regenera)
     *
     * @param int $visitId
     * @return JsonResponse
     */
    public function regeneratePdf(int $visitId): JsonResponse
    {
        try {
            // Generar PDF en tiempo real
            $pdf = $this->pdfService->generateSync($visitId);
            $visit = $this->pdfService->getVisitWithRelations($visitId);
            $filename = $this->pdfService->generateFilename($visit);

            return response()->json([
                'message' => 'PDF generado exitosamente',
                'visit_id' => $visitId,
                'filename' => $filename
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generando PDF', [
                'visit_id' => $visitId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'error' => 'Error al generar el PDF',
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
