<?php

namespace App\Services;

use App\DTOs\DeliveryExportData;
use App\Models\Delivery;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExportDeliveryEppService
{
    /**
     * Ruta relativa o absoluta de la plantilla excel
     */
    protected string $templatePath;

    public function __construct()
    {
        $resourcePath = resource_path('format/F-SST-SAT-016_FORMATO_DE_ENTREGA_DE_EPPS_NUEVO.xlsx');
        $basePath = base_path('format/F-SST-SAT-016_FORMATO_DE_ENTREGA_DE_EPPS_NUEVO.xlsx');

        if (file_exists($resourcePath)) {
            $this->templatePath = $resourcePath;
        } elseif (file_exists($basePath)) {
            $this->templatePath = $basePath;
        } else {
            throw new \Exception("Plantilla Excel no encontrada. Buscado en: {$resourcePath} y {$basePath}");
        }
    }

    /**
     * Exporta los datos de una entrega usando la plantilla Excel
     *
     * @param DeliveryExportData $data
     * @return string Ruta del archivo generado
     */
    public function export(DeliveryExportData $data): string
    {
        $spreadsheet = IOFactory::load($this->templatePath);
        $sheet = $spreadsheet->getActiveSheet();

        // 1. Rellenar datos del cabezal del trabajador
        $sheet->setCellValue('A14', $data->employeeName);
        $sheet->setCellValue('N14', $data->employeeDni);
        $sheet->setCellValue('O14', $data->employeeArea);
        $sheet->setCellValue('Q1', $data->documentCode);
        $sheet->setCellValue('Q2', $data->exportDate);

        // Rellenar datos del creador / responsable del registro
        $sheet->setCellValue('C35', $data->creatorFirstName);
        $sheet->setCellValue('A36', $data->creatorLastName);
        $sheet->setCellValue('C37', $data->creatorPosition);
        $sheet->setCellValue('C38', $data->creatorFullDate);

        // 2. Rellenar los ítems
        $startRow = 18;
        $maxPredefinedRows = 13; // filas 18 a 30 inclusive en la plantilla

        $totalItems = count($data->items);

        for ($i = 0; $i < $totalItems; $i++) {
            $currentRow = $startRow + $i;

            // Si superamos las 13 filas predefinidas en la plantilla, insertamos una nueva fila
            if ($i >= $maxPredefinedRows) {
                $sheet->insertNewRowBefore($currentRow, 1);

                // Replicar las celdas fusionadas de la fila modelo anterior
                $sheet->mergeCells("E{$currentRow}:I{$currentRow}");
                $sheet->mergeCells("J{$currentRow}:K{$currentRow}");
                $sheet->mergeCells("L{$currentRow}:M{$currentRow}");
                $sheet->mergeCells("P{$currentRow}:Q{$currentRow}");

                // Copiar estilos de bordes y formatos de la fila anterior
                $this->copyRowStyles($sheet, $currentRow - 1, $currentRow);
            }

            $item = $data->items[$i];

            $sheet->setCellValue("A{$currentRow}", $i + 1);
            $sheet->setCellValue("B{$currentRow}", 'X');
            $sheet->setCellValue("C{$currentRow}", ' ');
            $sheet->setCellValue("D{$currentRow}", $item->sku);
            $sheet->setCellValue("E{$currentRow}", $item->type);
            $sheet->setCellValue("J{$currentRow}", $item->quantity);
            $sheet->setCellValue("L{$currentRow}", $item->deliveredAt ? $item->deliveredAt->format('d/m/Y') : '');
            $sheet->setCellValue("N{$currentRow}", ' ');
            $sheet->setCellValue("O{$currentRow}", $item->notes);
            $sheet->setCellValue("P{$currentRow}", ' ');
        }

        // Crear directorio temporal si no existe
        $tempDir = storage_path('app/public/exports');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filename = 'entrega_epp_' . time() . '_' . uniqid() . '.xlsx';
        $tempPath = $tempDir . '/' . $filename;

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempPath);

        return $tempPath;
    }

    /**
     * Ejecuta la exportación directamente desde el modelo Delivery
     *
     * @param Delivery $delivery
     * @return string Ruta del archivo generado
     */
    public function exportFromModel(Delivery $delivery): string
    {
        $dto = DeliveryExportData::fromModel($delivery);
        return $this->export($dto);
    }

    /**
     * Copia los estilos de celda de una fila a otra
     *
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param int $fromRow
     * @param int $toRow
     * @return void
     */
    private function copyRowStyles($sheet, int $fromRow, int $toRow): void
    {
        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q'] as $col) {
            $fromCell = $sheet->getCell($col . $fromRow);
            $toCell = $sheet->getCell($col . $toRow);
            $toCell->setXfIndex($fromCell->getXfIndex());
        }
    }
}
