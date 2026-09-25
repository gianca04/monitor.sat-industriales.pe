<?php

namespace App\Services;

use App\Models\Requirement;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RequirementExportService
{
    /**
     * Exporta todos los ítems vinculados a un requerimiento a Excel.
     *
     * @return string Ruta absoluta del archivo temporal generado
     */
    public function exportAll(Requirement $requirement): string
    {
        $requirementLists = $requirement->requirementLists()->with(['item', 'item.unit'])->get();

        return $this->export($requirement, $requirementLists);
    }

    /**
     * Exporta una lista personalizada de ítems de un requerimiento a Excel.
     *
     * @param  Collection|array  $requirementLists  Colección de modelos RequirementList
     * @return string Ruta absoluta del archivo temporal generado
     */
    public function export(Requirement $requirement, $requirementLists): string
    {
        // Ruta de la plantilla
        $templatePath = base_path('format/LISTA_REQUERIMIENTOS.xlsx');

        if (! file_exists($templatePath)) {
            throw new \Exception("Plantilla no encontrada: {$templatePath}");
        }

        // Cargar plantilla
        $spreadsheet = IOFactory::load($templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Cabecera A1
        $subClientName = $requirement->subClient->name ?? 'N/A';
        $activityName = $requirement->activity_name ?? 'N/A';
        $createdAt = $requirement->created_at ? $requirement->created_at->format('d/m/Y') : 'N/A';
        $creatorName = $requirement->creator->name ?? 'N/A';

        $headerText = "{$subClientName} | {$activityName} | {$createdAt} | {$creatorName}";
        $sheet->setCellValue('A1', $headerText);

        // 2. Iterar sobre la lista de ítems a partir de la fila 4
        $startRow = 4;
        $currentRow = $startRow;

        $tempFiles = []; // Para limpiar las imágenes temporales después

        foreach ($requirementLists as $reqList) {
            $item = $reqList->item;
            if (! $item) {
                continue;
            }

            // Altura de la fila a 60
            $sheet->getRowDimension($currentRow)->setRowHeight(60);

            // A: Nombre
            $sheet->setCellValue('A'.$currentRow, $item->name);

            // B: Unidad
            $unitName = $item->unit->name ?? ($item->unit->symbol ?? 'UND');
            $sheet->setCellValue('B'.$currentRow, $unitName);

            // C: Cantidad
            $sheet->setCellValue('C'.$currentRow, $reqList->quantity);

            // D: Imagen
            if ($item->photo) {
                try {
                    // Usamos el servicio de fotos para obtener la URL pública o la ruta
                    $photoUrl = $item->photo_url;

                    if ($photoUrl) {
                        // Descargar la imagen a un archivo temporal local
                        $tempImagePath = sys_get_temp_dir().'/'.Str::uuid().'.jpg'; // PhpSpreadsheet a veces prefiere extensiones conocidas

                        // Ignorar advertencias de SSL en entorno local si es necesario, o timeout corto
                        $context = stream_context_create([
                            'http' => ['timeout' => 5],
                            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
                        ]);

                        $imageContent = @file_get_contents($photoUrl, false, $context);

                        if ($imageContent !== false) {
                            file_put_contents($tempImagePath, $imageContent);
                            $tempFiles[] = $tempImagePath;

                            // Insertar la imagen en el excel
                            $drawing = new Drawing;
                            $drawing->setName($item->name);
                            $drawing->setDescription($item->name);
                            $drawing->setPath($tempImagePath);
                            $drawing->setCoordinates('D'.$currentRow);

                            // Ajustar tamaño para que encaje en la celda (alto de fila es 60, que son aprox 80 pixels)
                            $drawing->setHeight(70);
                            $drawing->setOffsetX(5); // Pequeño margen
                            $drawing->setOffsetY(5);
                            $drawing->setWorksheet($sheet);
                        }
                    }
                } catch (\Exception $e) {
                    // Si falla la descarga de la imagen, simplemente no se inserta
                    // Log::warning("No se pudo cargar la imagen para el ítem {$item->id}: " . $e->getMessage());
                }
            }

            $currentRow++;
        }

        // 3. Guardar el archivo modificado en una ruta temporal
        $outputTempPath = sys_get_temp_dir().'/REQUERIMIENTO_'.$requirement->id.'_'.time().'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($outputTempPath);

        // Limpiar imágenes temporales
        foreach ($tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }

        return $outputTempPath;
    }
}
