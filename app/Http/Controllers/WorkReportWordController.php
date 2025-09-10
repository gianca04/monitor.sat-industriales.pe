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
            'project',
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
            'marginTop' => 1440, // 2.54 cm en puntos
            'marginLeft' => 1440, // 2.54 cm en puntos
            'marginRight' => 1440, // 2.54 cm en puntos
            'marginBottom' => 1440 // 2.54 cm en puntos
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

        $table = $section->addTable(['width' => 100 * 50]); // Configurar la tabla para ocupar todo el ancho disponible

        // Agregar una fila
        $table->addRow();

        // Primera celda con imagen
        $imgPath1 = public_path('images/Logo2.png');
        if (file_exists($imgPath1)) {
            $table->addCell(5000)->addImage($imgPath1, [
                'width' => 100,
                'height' => 100,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
            ]);
        } else {
            $table->addCell(5000)->addText('Imagen no disponible', ['name' => 'Times New Roman', 'size' => 12]);
        }

        // Segunda celda con texto
        $table->addCell(5000)->addText($workReport->project->name, [
            'name' => 'Times New Roman',
            'size' => 12,
            'align' => 'center'
        ]);

        // Tercera celda con imagen
        $imgPath2 = public_path('storage/' . $workReport->project->subClient->client->logo);
        if (file_exists($imgPath2)) {
            $table->addCell(5000)->addImage($imgPath2, [
                'width' => 100,
                'height' => 100,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
            ]);
        } else {
            $table->addCell(5000)->addText('Imagen no disponible', ['name' => 'Times New Roman', 'size' => 12]);
        }

        // Footer
        $section->addText('SAT INDUSTRIALES - Monitor', $fontStyle);

        // Descargar el archivo
        $filename = 'reporte_trabajo_' . $workReport->id . '_' . now()->format('Y-m-d_H-i') . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'word');
        $phpWord->save($tempFile, 'Word2007');

        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
}
