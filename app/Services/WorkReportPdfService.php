<?php

namespace App\Services;

use App\Models\WorkReport;
use App\Jobs\GenerateWorkReportPdfJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class WorkReportPdfService
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
     * Tiempo de caché para PDFs (en minutos)
     */
    private const CACHE_TTL = 60;

    /**
     * Genera un PDF de forma síncrona
     *
     * @param int $workReportId
     * @param array $options
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateSync(int $workReportId, array $options = []): \Barryvdh\DomPDF\PDF
    {
        $workReport = $this->getWorkReportWithRelations($workReportId);
        $this->validateWorkReportData($workReport);
        
        $data = $this->prepareViewData($workReport);
        
        return $this->createPdf($data, $options);
    }

    /**
     * Genera un PDF de forma asíncrona usando Jobs
     *
     * @param int $workReportId
     * @param string|null $userEmail
     * @param bool $shouldEmail
     * @return void
     */
    public function generateAsync(int $workReportId, ?string $userEmail = null, bool $shouldEmail = false): void
    {
        GenerateWorkReportPdfJob::dispatch($workReportId, $userEmail, $shouldEmail)
            ->onQueue('pdfs'); // Queue específica para PDFs
    }

    /**
     * Obtiene un PDF desde caché o lo genera si no existe
     *
     * @param int $workReportId
     * @param bool $forceRegenerate
     * @return string|null Path del PDF o null si no existe
     */
    public function getCachedOrGenerate(int $workReportId, bool $forceRegenerate = false): ?string
    {
        $cacheKey = "work_report_pdf_{$workReportId}";
        
        if (!$forceRegenerate) {
            $cachedPath = Cache::get($cacheKey);
            if ($cachedPath && Storage::disk('public')->exists($cachedPath)) {
                Log::info('PDF obtenido desde caché', [
                    'work_report_id' => $workReportId,
                    'cached_path' => $cachedPath
                ]);
                return $cachedPath;
            }
        }

        try {
            $workReport = $this->getWorkReportWithRelations($workReportId);
            $this->validateWorkReportData($workReport);
            
            $data = $this->prepareViewData($workReport);
            $pdf = $this->createPdf($data);
            
            // Almacenar PDF
            $path = $this->storePdf($pdf, $workReport);
            
            // Cachear el path
            Cache::put($cacheKey, $path, now()->addMinutes(self::CACHE_TTL));
            
            Log::info('PDF generado y cacheado', [
                'work_report_id' => $workReportId,
                'path' => $path
            ]);
            
            return $path;
            
        } catch (\Exception $e) {
            Log::error('Error generando PDF cacheado', [
                'work_report_id' => $workReportId,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Obtiene el reporte con relaciones optimizadas
     *
     * @param int $workReportId
     * @return WorkReport
     */
    public function getWorkReportWithRelations(int $workReportId): WorkReport
    {
        return WorkReport::with([
            'employee:id,first_name,last_name',
            'project:id,name,start_date,end_date,sub_client_id,quote_id',
            'project.subClient:id,name,client_id',
            'project.subClient.client:id,business_name,document_number,logo',
            'project.quote:id,TDR',
            'photos' => function ($query) {
                $query->select([
                    'id', 
                    'work_report_id', 
                    'photo_path', 
                    'before_work_photo_path', 
                    'descripcion', 
                    'before_work_descripcion',
                    'created_at'
                ])->orderBy('created_at', 'asc');
            }
        ])->findOrFail($workReportId);
    }

    /**
     * Valida los datos del reporte
     *
     * @param WorkReport $workReport
     * @throws \Exception
     */
    public function validateWorkReportData(WorkReport $workReport): void
    {
        if (!$workReport->employee) {
            throw new \Exception('El reporte debe tener un empleado asignado');
        }

        if (!$workReport->project) {
            throw new \Exception('El reporte debe estar asociado a un proyecto');
        }
    }

    /**
     * Prepara los datos para la vista
     *
     * @param WorkReport $workReport
     * @return array
     */
    public function prepareViewData(WorkReport $workReport): array
    {
        return [
            'workReport' => $workReport,
            'employee' => $workReport->employee,
            'project' => $workReport->project,
            'photos' => $workReport->photos,
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

        return Pdf::loadView('reports.work-report-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions($options);
    }

    /**
     * Almacena el PDF en storage
     *
     * @param \Barryvdh\DomPDF\PDF $pdf
     * @param WorkReport $workReport
     * @return string Path del archivo
     */
    public function storePdf(\Barryvdh\DomPDF\PDF $pdf, WorkReport $workReport): string
    {
        $filename = $this->generateFilename($workReport);
        $path = "reports/work-reports/{$filename}";
        
        Storage::disk('public')->put($path, $pdf->output());
        
        return $path;
    }

    /**
     * Genera un nombre único para el archivo
     *
     * @param WorkReport $workReport
     * @return string
     */
    public function generateFilename(WorkReport $workReport): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $reportName = Str::slug($workReport->name ?? 'reporte', '_');
        
        return "reporte_trabajo_{$workReport->id}_{$reportName}_{$timestamp}.pdf";
    }

    /**
     * Limpia archivos PDF antiguos
     *
     * @param int $daysOld
     * @return int Número de archivos eliminados
     */
    public function cleanupOldPdfs(int $daysOld = 30): int
    {
        $files = Storage::disk('public')->files('reports/work-reports');
        $cutoffDate = now()->subDays($daysOld);
        $deletedCount = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);
            
            if ($lastModified < $cutoffDate->timestamp) {
                Storage::disk('public')->delete($file);
                $deletedCount++;
            }
        }

        Log::info('Limpieza de PDFs antiguos completada', [
            'days_old' => $daysOld,
            'deleted_count' => $deletedCount
        ]);

        return $deletedCount;
    }

    /**
     * Obtiene estadísticas de PDFs generados
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $files = Storage::disk('public')->files('reports/work-reports');
        $totalSize = 0;
        
        foreach ($files as $file) {
            $totalSize += Storage::disk('public')->size($file);
        }

        return [
            'total_files' => count($files),
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2),
            'storage_path' => Storage::disk('public')->path('reports/work-reports')
        ];
    }
}
