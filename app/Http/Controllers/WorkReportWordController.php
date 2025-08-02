<?php

namespace App\Http\Controllers;

use App\Models\WorkReport;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc; // Agrega esta línea al inicio con los use

class WorkReportWordController extends Controller
{
    public function generateReport($workReportId)
    {
        $workReport = WorkReport::with([
            'employee',
            'project.clients',
            'photos' => function ($query) {
                $query->orderBy('created_at', 'asc');
            }
        ])->findOrFail($workReportId);

        // Verificar que el reporte tenga fotografías
        if ($workReport->photos->isEmpty()) {
            return redirect()->back()->with('error', 'No se puede generar el reporte sin evidencias fotográficas.');
        }

        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginTop' => 600, // Márgenes en 1 cm
            'marginLeft' => 600,
            'marginRight' => 600,
            'marginBottom' => 600
        ]);

        // Estilo de fuente por defecto
        $fontStyle = [
            'name' => 'Times New Roman',
            'size' => 12,
            'align' => 'both'
        ];
        $headingStyle = [
            'name' => 'Times New Roman',
            'size' => 14,
            'bold' => true
        ];

        // Título
        $section->addText($workReport->project->name, ['bold' => true, 'size' => 18, 'name' => 'Times New Roman']);
        $section->addText('Reporte #' . $workReport->id, $headingStyle);
        $section->addTextBreak();

        // Información General
        $section->addText('Información del Reporte', $headingStyle);
        $section->addText('Nombre: ' . $workReport->name, $fontStyle);
        $section->addText('Descripción: ' . ($workReport->description ?? 'N/A'), $fontStyle);
        $section->addText('Fecha de creación: ' . $workReport->created_at->format('d/m/Y H:i'), $fontStyle);
        $section->addTextBreak();

        // Supervisor
        $section->addText('Supervisor Responsable', $headingStyle);
        $section->addText('Nombre: ' . $workReport->employee->first_name . ' ' . $workReport->employee->last_name, $fontStyle);
        $section->addText('Documento: ' . $workReport->employee->document_type . ' ' . $workReport->employee->document_number, $fontStyle);
        if ($workReport->employee->user) {
            $section->addText('Email: ' . $workReport->employee->user->email, $fontStyle);
        }
        $section->addTextBreak();

        // Proyecto
        $section->addText('Proyecto', $headingStyle);
        $section->addText('Nombre: ' . $workReport->project->name, $fontStyle);
        $section->addText('Código: ' . ($workReport->project->quote_id ?? 'N/A'), $fontStyle);
        $section->addText('Estado: ' . ($workReport->project->status ?? 'Activo'), $fontStyle);
        if ($workReport->project->start_date) {
            $section->addText('Fecha inicio: ' . \Carbon\Carbon::parse($workReport->project->start_date)->format('d/m/Y'), $fontStyle);
        }
        $section->addTextBreak();

        // Estadísticas
        $section->addText('Total Evidencias: ' . $workReport->photos->count(), $fontStyle);
        $section->addText('Evidencias Hoy: ' . $workReport->photos->where('created_at', '>=', today())->count(), $fontStyle);
        $diasTrabajo = collect($workReport->photos)->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        })->count();
        $section->addText('Días de Trabajo: ' . $diasTrabajo, $fontStyle);
        $section->addTextBreak();

        // Fotos
        $section->addText('Evidencias Fotográficas', $headingStyle);
        foreach ($workReport->photos as $index => $photo) {
            $section->addText('Evidencia #' . ($index + 1), ['bold' => true, 'size' => 12]);
            $section->addText('Capturada el: ' . $photo->created_at->format('d/m/Y H:i'), $fontStyle);
            $section->addText('Descripción: ' . $photo->descripcion, $fontStyle);

            // Si la imagen existe, agregarla
            $imgPath = public_path('storage/' . $photo->photo_path);
            if (file_exists($imgPath)) {
                list($origWidth, $origHeight) = getimagesize($imgPath);
                $maxWidth = 400;
                $maxHeight = 250;
                $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight, 1);
                $newWidth = intval($origWidth * $ratio);
                $newHeight = intval($origHeight * $ratio);
                $section->addImage($imgPath, [
                    'width' => $newWidth,
                    'height' => $newHeight,
                    'alignment' => Jc::CENTER // Centrar imagen
                ]);
            } else {
                $section->addText('Imagen no disponible', $fontStyle);
            }
            $section->addTextBreak();
        }

        // Footer
        $section->addText('Reporte generado automáticamente el ' . now()->format('d/m/Y H:i'), $fontStyle);
        $section->addText('SAT INDUSTRIALES - Monitor', $fontStyle);

        // Descargar el archivo
        $filename = 'reporte_trabajo_' . $workReport->id . '_' . now()->format('Y-m-d_H-i') . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'word');
        $phpWord->save($tempFile, 'Word2007');

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
