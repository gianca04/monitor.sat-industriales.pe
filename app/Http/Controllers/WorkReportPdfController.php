<?php

namespace App\Http\Controllers;

use App\Models\WorkReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class WorkReportPdfController extends Controller
{
    public function generateReport($workReportId)
    {
        $workReport = WorkReport::with([
            'employee',
            'project.clients',
            'photos' => function ($query) {
                $query->orderBy('taken_at', 'asc');
            }
        ])->findOrFail($workReportId);

        // Verificar que el reporte tenga fotografías
        if ($workReport->photos->isEmpty()) {
            return redirect()->back()->with('error', 'No se puede generar el reporte sin evidencias fotográficas.');
        }

        $data = [
            'workReport' => $workReport,
            'employee' => $workReport->employee,
            'project' => $workReport->project,
            'photos' => $workReport->photos,
            'generatedAt' => now(),
        ];

        $pdf = Pdf::loadView('reports.work-report-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'Arial',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'dpi' => 150,
                'defaultPaperSize' => 'a4',
                'chroot' => public_path(),
            ]);

        $filename = 'reporte_trabajo_' . $workReport->id . '_' . now()->format('Y-m-d_H-i') . '.pdf';

        return $pdf->download($filename);
    }
}
