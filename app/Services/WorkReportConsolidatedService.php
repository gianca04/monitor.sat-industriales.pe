<?php

namespace App\Services;

use App\Models\Project;
use App\Models\WorkReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Log;

class WorkReportConsolidatedService
{
    /**
     * Genera un PDF consolidado con todos los reportes de trabajo de un proyecto
     *
     * @param int $projectId
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateConsolidatedPdf(int $projectId): \Barryvdh\DomPDF\PDF
    {
        // Obtener el proyecto con sus relaciones
        $project = $this->getProjectWithRelations($projectId);
        
        // Obtener todos los reportes de trabajo del proyecto ordenados por fecha
        $workReports = $this->getProjectWorkReports($projectId);
        
        // Obtener todas las fotos de todos los reportes
        $allPhotos = $this->getAllPhotosFromReports($workReports);
        
        Log::info('Generando PDF consolidado', [
            'project_id' => $projectId,
            'work_reports_count' => $workReports->count(),
            'total_photos' => $allPhotos->count()
        ]);
        
        // Generar el PDF usando la vista consolidada
        $pdf = Pdf::loadView('reports.work-report-consolidated-pdf', [
            'project' => $project,
            'workReports' => $workReports,
            'allPhotos' => $allPhotos,
            'generatedAt' => now()
        ]);
        
        // Configuración del PDF igual que en WorkReportPdfService
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
     * Obtiene el proyecto con todas sus relaciones necesarias
     *
     * @param int $projectId
     * @return Project
     */
    public function getProjectWithRelations(int $projectId): Project
    {
        return Project::with([
            'subClient.client',
            'quote',
            'workReports.employee',
            'workReports.photos'
        ])->findOrFail($projectId);
    }
    
    /**
     * Obtiene todos los reportes de trabajo del proyecto ordenados por fecha
     *
     * @param int $projectId
     * @return Collection
     */
    public function getProjectWorkReports(int $projectId): Collection
    {
        return WorkReport::with(['employee', 'photos'])
            ->where('project_id', $projectId)
            ->orderBy('report_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();
    }
    
    /**
     * Obtiene todas las fotos de todos los reportes
     *
     * @param Collection $workReports
     * @return SupportCollection
     */
    private function getAllPhotosFromReports(Collection $workReports): SupportCollection
    {
        return $workReports->pluck('photos')->flatten();
    }
    
    /**
     * Genera el nombre del archivo para el PDF consolidado
     *
     * @param Project $project
     * @return string
     */
    public function generateConsolidatedFilename(Project $project): string
    {
        $clientName = $project->subClient->client->business_name ?? 'Cliente';
        $projectName = $project->name ?? 'Proyecto';
        $date = now()->format('Y-m-d');
        
        // Limpiar caracteres especiales para el nombre del archivo
        $clientName = $this->sanitizeFilename($clientName);
        $projectName = $this->sanitizeFilename($projectName);
        
        return "reporte-consolidado-{$clientName}-{$projectName}-{$date}.pdf";
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
     * @param int $projectId
     * @return array
     */
    public function getConsolidatedStatistics(int $projectId): array
    {
        $workReports = $this->getProjectWorkReports($projectId);
        $project = $this->getProjectWithRelations($projectId);
        
        $totalPhotos = $workReports->sum(function ($report) {
            return $report->photos->count();
        });
        
        $dateRange = [
            'start' => $workReports->min('report_date'),
            'end' => $workReports->max('report_date')
        ];
        
        $totalWorkingHours = $workReports->sum(function ($report) {
            if ($report->start_time && $report->end_time) {
                $start = \Carbon\Carbon::parse($report->start_time);
                $end = \Carbon\Carbon::parse($report->end_time);
                return $end->diffInHours($start);
            }
            return 0;
        });
        
        return [
            'project_name' => $project->name,
            'client_name' => $project->subClient->client->business_name ?? 'N/A',
            'total_reports' => $workReports->count(),
            'total_photos' => $totalPhotos,
            'date_range' => $dateRange,
            'total_working_hours' => $totalWorkingHours,
            'employees_involved' => $workReports->pluck('employee.full_name')->unique()->values()->toArray()
        ];
    }
}