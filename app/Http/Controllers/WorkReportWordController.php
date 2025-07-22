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
                $query->orderBy('taken_at', 'asc');
            }
        ])->findOrFail($workReportId);

        // Verificar que el reporte tenga fotografías
        if ($workReport->photos->isEmpty()) {
            return redirect()->back()->with('error', 'No se puede generar el reporte sin evidencias fotográficas.');
        }

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Título
        $section->addText($workReport->project->name, ['bold' => true, 'size' => 18]);
        $section->addText('Reporte #' . $workReport->id, ['bold' => true, 'size' => 14]);
        $section->addTextBreak();

        // Información General
        $section->addText('Información del Reporte', ['bold' => true, 'size' => 14]);
        $section->addText('Nombre: ' . $workReport->name);
        $section->addText('Descripción: ' . ($workReport->description ?? 'N/A'));
        $section->addText('Fecha de creación: ' . $workReport->created_at->format('d/m/Y H:i'));
        $section->addTextBreak();

        // Supervisor
        $section->addText('Supervisor Responsable', ['bold' => true, 'size' => 14]);
        $section->addText('Nombre: ' . $workReport->employee->first_name . ' ' . $workReport->employee->last_name);
        $section->addText('Documento: ' . $workReport->employee->document_type . ' ' . $workReport->employee->document_number);
        if ($workReport->employee->user) {
            $section->addText('Email: ' . $workReport->employee->user->email);
        }
        $section->addTextBreak();

        // Proyecto
        $section->addText('Proyecto', ['bold' => true, 'size' => 14]);
        $section->addText('Nombre: ' . $workReport->project->name);
        $section->addText('Código: ' . ($workReport->project->quote_id ?? 'N/A'));
        $section->addText('Estado: ' . ($workReport->project->status ?? 'Activo'));
        if ($workReport->project->start_date) {
            $section->addText('Fecha inicio: ' . \Carbon\Carbon::parse($workReport->project->start_date)->format('d/m/Y'));
        }
        $section->addTextBreak();

        // Estadísticas
        $section->addText('Total Evidencias: ' . $workReport->photos->count());
        $section->addText('Evidencias Hoy: ' . $workReport->photos->where('taken_at', '>=', today())->count());
        $diasTrabajo = collect($workReport->photos)->groupBy(function ($item) {
            return $item->taken_at->format('Y-m-d');
        })->count();
        $section->addText('Días de Trabajo: ' . $diasTrabajo);
        $section->addTextBreak();

        // Fotos
        $section->addText('Evidencias Fotográficas', ['bold' => true, 'size' => 14]);
        foreach ($workReport->photos as $index => $photo) {
            $section->addText('Evidencia #' . ($index + 1), ['bold' => true]);
            $section->addText('Capturada el: ' . $photo->taken_at->format('d/m/Y H:i'));
            $section->addText('Descripción: ' . $photo->descripcion);

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
                $section->addText('Imagen no disponible');
            }
            $section->addTextBreak();
        }

        // Footer
        $section->addText('Reporte generado automáticamente el ' . now()->format('d/m/Y H:i'));
        $section->addText('SAT INDUSTRIALES - Monitor');

        // Descargar el archivo
        $filename = 'reporte_trabajo_' . $workReport->id . '_' . now()->format('Y-m-d_H-i') . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'word');
        $phpWord->save($tempFile, 'Word2007');

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
