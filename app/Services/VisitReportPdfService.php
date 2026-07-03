<?php

namespace App\Services;

use App\Models\Visit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class VisitReportPdfService
{
    /**
     * Configuración por defecto para PDFs
     */
    private const PDF_OPTIONS = [
        'defaultFont' => 'DejaVu Sans',
        'isHtml5ParserEnabled' => true,
        'isPhpEnabled' => true,
        'dpi' => 150,
        'defaultPaperSize' => 'a4',
        'enable_php' => true,
        'enable_javascript' => false,
        'enable_remote' => false,
        'enable_html5_parser' => true,
    ];



    /**
     * Genera un PDF de forma síncrona
     *
     * @param int $visitId
     * @param array $options
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateSync(int $visitId, array $options = []): \Barryvdh\DomPDF\PDF
    {
        $visit = $this->getVisitWithRelations($visitId);
        $this->validateVisitData($visit);
        
        $data = $this->prepareViewData($visit);
        
        return $this->createPdf($data, $options);
    }

    /**
     * Genera un PDF de forma asíncrona usando Jobs
     *
     * @param int $visitId
     * @param string|null $userEmail
     * @param bool $shouldEmail
     * @return void
     */
    public function generateAsync(int $visitId, ?string $userEmail = null, bool $shouldEmail = false): void
    {
        // TODO: Implementar Job para generación asíncrona si es necesario
        Log::info('Generación asíncrona solicitada', [
            'visit_id' => $visitId,
            'user_email' => $userEmail,
            'should_email' => $shouldEmail
        ]);
    }



    /**
     * Obtiene la visita con relaciones optimizadas
     *
     * @param int $visitId
     * @return Visit
     */
    public function getVisitWithRelations(int $visitId): Visit
    {
        return Visit::select([
            'id',
            'employee_id',
            'name',
            'description',
            'employee_signature',
            'manager_signature',
            'suggestions',
            'tools',
            'materials',
            'start_time',
            'end_time',
            'report_date',
            'created_at',
            'updated_at'
        ])->with([
            'employee:id,first_name,last_name,document_number',
            'visitPhotos' => function ($query) {
                $query->select([
                    'visit_photos.id',
                    'visit_photos.visit_id', 
                    'visit_photos.photo_path', 
                    'visit_photos.descripcion',
                    'visit_photos.created_at'
                ])->orderBy('visit_photos.created_at', 'asc');
            },
            'requests' => function ($query) {
                $query->select([
                    'requests.id',
                    'requests.reference',
                    'requests.description',
                    'requests.status',
                    'requests.sub_client_id'
                ])->with([
                    'subClient:id,name,address,client_id',
                    'subClient.client:id,business_name,document_number,logo'
                ]);
            }
        ])->findOrFail($visitId);
    }

    /**
     * Valida los datos de la visita
     *
     * @param Visit $visit
     * @throws \Exception
     */
    public function validateVisitData(Visit $visit): void
    {
        if (!$visit->employee) {
            throw new \Exception('La visita debe tener un empleado asignado');
        }

        if (empty($visit->name)) {
            throw new \Exception('La visita debe tener un nombre');
        }
    }

    /**
     * Prepara los datos para la vista
     *
     * @param Visit $visit
     * @return array
     */
    public function prepareViewData(Visit $visit): array
    {
        // Obtener información del cliente principal si existe
        $mainClient = null;
        $mainSubClient = null;
        
        if ($visit->requests->isNotEmpty()) {
            $firstRequest = $visit->requests->first();
            if ($firstRequest->subClient) {
                $mainSubClient = $firstRequest->subClient;
                $mainClient = $firstRequest->subClient->client;
            }
        }

        return [
            'visit' => $visit,
            'employee' => $visit->employee,
            'visitPhotos' => $visit->visitPhotos,
            'requests' => $visit->requests,
            'mainClient' => $mainClient,
            'mainSubClient' => $mainSubClient,
            'generatedAt' => now(),
        ];
    }

    /**
     * Crea el objeto PDF
     *
     * @param array $data
     * @param array $customOptions
     * @return \Barryvdh\DomPDF\PDF
     */
    public function createPdf(array $data, array $customOptions = []): \Barryvdh\DomPDF\PDF
    {
        $options = array_merge(self::PDF_OPTIONS, [
            'chroot' => [
                public_path('storage'), 
                public_path('images'),
                storage_path('app/public')
            ]
        ], $customOptions);

        return Pdf::loadView('reports.visit-report-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions($options);
    }

    /**
     * Genera un nombre único para el archivo
     *
     * @param Visit $visit
     * @return string
     */
    public function generateFilename(Visit $visit): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $visitName = Str::slug($visit->name ?? 'visita', '_');
        
        return "reporte_visita_{$visit->id}_{$visitName}_{$timestamp}.pdf";
    }


}
