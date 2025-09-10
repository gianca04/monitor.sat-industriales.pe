<?php

namespace App\Exports;

use App\Models\Employee;
use App\Models\Project;
use App\Models\Timesheet;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceTemplateExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithTitle
{
    protected $projectId;
    protected $date;

    public function __construct($projectId = null, $date = null)
    {
        $this->projectId = $projectId;
        $this->date = $date ?? now()->format('Y-m-d');
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Si se especifica un proyecto, obtener todos los empleados
        // Si no, crear una plantilla con algunos empleados de ejemplo
        if ($this->projectId) {
            return Employee::active()->get();
        }

        // Plantilla de ejemplo
        return collect([
            (object) [
                'id' => 1,
                'document_number' => '12345678',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'full_name' => 'Juan Pérez'
            ],
            (object) [
                'id' => 2,
                'document_number' => '87654321',
                'first_name' => 'María',
                'last_name' => 'García',
                'full_name' => 'María García'
            ],
            (object) [
                'id' => 3,
                'document_number' => '11223344',
                'first_name' => 'Carlos',
                'last_name' => 'López',
                'full_name' => 'Carlos López'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'Documento',
            'Nombre Completo',
            'Estado',
            'Turno',
            'Fecha Entrada',
            'Inicio Descanso',
            'Fin Descanso',
            'Fecha Salida',
            'Observación'
        ];
    }

    public function map($employee): array
    {
        return [
            $employee->document_number ?? '',
            $employee->full_name ?? ($employee->first_name . ' ' . $employee->last_name),
            'Asistió', // Estado por defecto
            'Día', // Turno por defecto
            '', // Fecha entrada (se llenará automáticamente o manualmente)
            '', // Inicio descanso
            '', // Fin descanso
            '', // Fecha salida
            '' // Observación
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Documento
            'B' => 25, // Nombre Completo
            'C' => 15, // Estado
            'D' => 12, // Turno
            'E' => 18, // Fecha Entrada
            'F' => 18, // Inicio Descanso
            'G' => 18, // Fin Descanso
            'H' => 18, // Fecha Salida
            'I' => 30, // Observación
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para el encabezado
        $sheet->getStyle('A1:I1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Estilo para las filas de datos
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > 1) {
            $sheet->getStyle('A2:I' . $lastRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Alternar colores de fila
            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA'],
                        ],
                    ]);
                }
            }
        }

        // Información adicional en la parte superior
        $sheet->insertNewRowBefore(1, 4);

        // Título principal
        $sheet->setCellValue('A1', 'PLANTILLA DE ASISTENCIAS - TAREO');
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '1F4E79'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Información del proyecto y fecha
        if ($this->projectId) {
            $project = Project::find($this->projectId);
            $sheet->setCellValue('A2', 'Proyecto: ' . ($project->name ?? 'No especificado'));
        } else {
            $sheet->setCellValue('A2', 'Proyecto: [Especificar proyecto]');
        }
        $sheet->setCellValue('A3', 'Fecha: ' . Carbon::parse($this->date)->format('d/m/Y'));

        $sheet->getStyle('A2:A3')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        // Instrucciones
        $sheet->setCellValue('A4', 'INSTRUCCIONES:');
        $sheet->setCellValue('A5', '• Estado: Escribir "Asistió", "Faltó" o "Justificado"');
        $sheet->setCellValue('A6', '• Turno: Escribir "Día" o "Noche"');
        $sheet->setCellValue('A7', '• Fechas: Usar formato dd/mm/yyyy hh:mm (ej: 15/12/2024 08:00)');
        $sheet->setCellValue('A8', '• Dejar campos de fecha vacíos si no aplican');

        $sheet->getStyle('A4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'D32F2F']],
        ]);

        $sheet->getStyle('A5:A8')->applyFromArray([
            'font' => ['size' => 9, 'color' => ['rgb' => '666666']],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Plantilla Asistencias';
    }
}
