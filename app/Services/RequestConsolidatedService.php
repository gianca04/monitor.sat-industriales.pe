<?php

namespace App\Services;

use App\Models\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;

class RequestConsolidatedService
{
    /**
     * Genera un PDF consolidado con todas las visitas de un request
     *
     * @param int $requestId
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateConsolidatedPdf(int $requestId): \Barryvdh\DomPDF\PDF
    {
        // Obtener el request con sus relaciones
        $request = $this->getRequestWithRelations($requestId);

        // Obtener todas las visitas del request ordenadas por fecha
        $visits = $this->getRequestVisits($requestId);

        // Obtener todas las fotos de todas las visitas
        $allPhotos = $this->getAllPhotosFromVisits($visits);

        Log::info('Generando PDF consolidado de request', [
            'request_id' => $requestId,
            'visits_count' => $visits->count(),
            'total_photos' => $allPhotos->count()
        ]);

        // Generar el PDF usando la vista consolidada
        $pdf = Pdf::loadView('reports.request-consolidated-pdf', [
            'request' => $request,
            'visits' => $visits,
            'allPhotos' => $allPhotos,
            'generatedAt' => now()
        ]);

        // Configuración del PDF igual que en otros servicios
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled' => true,
            'dpi' => 150,
            'defaultPaperSize' => 'a4',
            'enable_php' => true,
            'enable_javascript' => false,
            'enable_remote' => false,
            'enable_html5_parser' => true,
            'chroot' => [
                public_path('storage'),
                public_path('images'),
                storage_path('app/public')
            ]
        ]);

        return $pdf;
    }

    /**
     * Obtiene el request con todas sus relaciones necesarias
     *
     * @param int $requestId
     * @return Request
     */
    public function getRequestWithRelations(int $requestId): Request
    {
        return Request::with([
            'subClient.client',
            'cotizador',
            'supervisor',
            'visits.employee',
            'visits.visitPhotos'
        ])->findOrFail($requestId);
    }

    /**
     * Obtiene todas las visitas del request ordenadas por fecha
     *
     * @param int $requestId
     * @return Collection
     */
    public function getRequestVisits(int $requestId): Collection
    {
        return Request::findOrFail($requestId)
            ->visitas()
            ->with(['employee', 'visitPhotos'])
            ->orderBy('report_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Obtiene todas las fotos de todas las visitas
     *
     * @param Collection $visits
     * @return SupportCollection
     */
    private function getAllPhotosFromVisits(Collection $visits): SupportCollection
    {
        return $visits->pluck('visitPhotos')->flatten();
    }

    /**
     * Genera el nombre del archivo para el PDF consolidado
     *
     * @param Request $request
     * @return string
     */
    public function generateConsolidatedFilename(Request $request): string
    {
        $clientName = $request->subClient->client->business_name ?? 'Cliente';
        $requestRef = $request->reference ?? 'Request';
        $date = now()->format('Y-m-d');

        // Limpiar caracteres especiales para el nombre del archivo
        $clientName = $this->sanitizeFilename($clientName);
        $requestRef = $this->sanitizeFilename($requestRef);

        return "reporte-consolidado-{$clientName}-{$requestRef}-{$date}.pdf";
    }

    /**
     * Limpia el nombre del archivo de caracteres especiales
     *
     * @param string $filename
     * @return string
     */
    private function sanitizeFilename(string $filename): string
    {
        // Reemplazar caracteres especiales y espacios
        $filename = preg_replace('/[^a-zA-Z0-9\-_.]/', '-', $filename);
        // Eliminar múltiples guiones seguidos
        $filename = preg_replace('/-+/', '-', $filename);
        // Eliminar guiones al inicio y final
        return trim($filename, '-');
    }

    /**
     * Obtiene estadísticas del reporte consolidado
     *
     * @param int $requestId
     * @return array
     */
    public function getConsolidatedStatistics(int $requestId): array
    {
        $visits = $this->getRequestVisits($requestId);
        $request = $this->getRequestWithRelations($requestId);

        $totalPhotos = $visits->sum(function ($visit) {
            return $visit->visitPhotos->count();
        });

        $dateRange = [
            'start' => $visits->min('report_date'),
            'end' => $visits->max('report_date')
        ];

        $totalWorkingHours = $visits->sum(function ($visit) {
            if ($visit->start_time && $visit->end_time) {
                $start = \Carbon\Carbon::parse($visit->start_time);
                $end = \Carbon\Carbon::parse($visit->end_time);
                return $end->diffInHours($start);
            }
            return 0;
        });

        return [
            'request_reference' => $request->reference,
            'client_name' => $request->subClient->client->business_name ?? 'N/A',
            'subclient_name' => $request->subClient->name ?? 'N/A',
            'total_visits' => $visits->count(),
            'total_photos' => $totalPhotos,
            'date_range' => $dateRange,
            'total_working_hours' => $totalWorkingHours,
            'employees_involved' => $visits->pluck('employee.full_name')->unique()->values()->toArray(),
            'visit_date' => $request->visit_date,
            'status' => $request->status
        ];
    }
}