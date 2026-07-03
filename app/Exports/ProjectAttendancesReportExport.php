<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\Timesheet;
use App\Models\Attendance;
use App\Services\HoursCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
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

class ProjectAttendancesReportExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithTitle
{
    protected $project;
    protected $startDate;
    protected $endDate;
    protected $attendances;

    public function __construct($projectId, $startDate, $endDate)
    {
        $this->project = Project::with(['subClient.client'])->find($projectId);
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
        
        // Obtener todas las asistencias del proyecto en el rango de fechas
        $this->attendances = $this->getProjectAttendances();
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->attendances;
    }

    public function headings(): array
    {
        return [
            'Fecha del Tareo',
            'Supervisor',
            'Documento',
            'Nombre Completo',
            'Estado',
            'Turno',
            'Fecha Entrada',
            'Inicio Descanso',
            'Fin Descanso',
            'Fecha Salida',
            'Horas Trabajadas',
            'Horas Extra',
            'Observación'
        ];
    }

    public function map($attendance): array
    {
        // Usar HoursCalculator para obtener todas las horas calculadas
        $hoursSummary = HoursCalculator::getHoursSummary($attendance);
        
        $horasTrabajadas = $hoursSummary['worked_hours']['formatted'] ?? 'NO CALCULADO';
        $horasExtra = $hoursSummary['extra_hours']['formatted'] ?? '0h 0m';

        // Información del supervisor del timesheet
        $supervisor = '';
        if ($attendance->timesheet && $attendance->timesheet->employee) {
            $supervisor = $attendance->timesheet->employee->first_name . ' ' . $attendance->timesheet->employee->last_name;
        }

        return [
            // Fecha del tareo
            $attendance->timesheet ? Carbon::parse($attendance->timesheet->check_in_date)->format('d/m/Y') : 'Sin fecha',
            // Supervisor
            $supervisor ?: 'Sin supervisor',
            // Documento del empleado
            $attendance->employee->document_number ?? '',
            // Nombre completo del empleado
            $attendance->employee->first_name . ' ' . $attendance->employee->last_name,
            // Estado de asistencia
            match($attendance->status) {
                'attended' => 'Asistió',
                'absent' => 'Faltó',
                'justified' => 'Justificado',
                'present' => 'Asistió',
                'late' => 'Llegó Tarde',
                default => ucfirst($attendance->status ?? 'Sin definir')
            },
            // Turno
            match($attendance->shift) {
                'day' => 'Día',
                'night' => 'Noche',
                default => ucfirst($attendance->shift ?? 'No definido')
            },
            // Fecha de entrada
            $attendance->check_in_date ? Carbon::parse($attendance->check_in_date)->format('d/m/Y H:i') : 'NO REGISTRADO',
            // Inicio descanso
            $attendance->break_date ? Carbon::parse($attendance->break_date)->format('d/m/Y H:i') : 'NO REGISTRADO',
            // Fin descanso
            $attendance->end_break_date ? Carbon::parse($attendance->end_break_date)->format('d/m/Y H:i') : 'NO REGISTRADO',
            // Fecha de salida
            $attendance->check_out_date ? Carbon::parse($attendance->check_out_date)->format('d/m/Y H:i') : 'NO REGISTRADO',
            // Horas trabajadas
            $horasTrabajadas,
            // Horas extra
            $horasExtra,
            // Observación
            $attendance->observation ?? ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 15, // Fecha del Tareo
            'B' => 25, // Supervisor
            'C' => 15, // Documento
            'D' => 25, // Nombre Completo
            'E' => 15, // Estado
            'F' => 12, // Turno
            'G' => 18, // Fecha Entrada
            'H' => 18, // Inicio Descanso
            'I' => 18, // Fin Descanso
            'J' => 18, // Fecha Salida
            'K' => 15, // Horas Trabajadas
            'L' => 15, // Horas Extra
            'M' => 30, // Observación
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Insertar información del proyecto en la parte superior
        $sheet->insertNewRowBefore(1, 6);

        // Título principal
        $sheet->setCellValue('A1', 'REPORTE DE ASISTENCIAS - PROYECTO');
        $sheet->mergeCells('A1:M1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '1F4E79'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Información del proyecto
        $sheet->setCellValue('A2', 'Proyecto: ' . $this->project->name);
        $sheet->setCellValue('A3', 'Cliente: ' . ($this->project->subClient?->client?->business_name ?? 'Sin cliente'));
        $sheet->setCellValue('A4', 'Subcliente: ' . ($this->project->subClient?->name ?? 'Sin subcliente'));
        $sheet->setCellValue('A5', 'Período: ' . $this->startDate->format('d/m/Y') . ' - ' . $this->endDate->format('d/m/Y'));
        $sheet->setCellValue('A6', 'Generado: ' . now()->format('d/m/Y H:i'));

        // Información estadística
        $totalAsistencias = $this->attendances->count();
        $totalAsistieron = $this->attendances->where('status', 'attended')->count();
        $totalFaltaron = $this->attendances->where('status', 'absent')->count();
        
        $sheet->setCellValue('G2', 'Total Registros: ' . $totalAsistencias);
        $sheet->setCellValue('G3', 'Asistieron: ' . $totalAsistieron);
        $sheet->setCellValue('G4', 'Faltaron: ' . $totalFaltaron);
        $sheet->setCellValue('G5', '% Asistencia: ' . ($totalAsistencias > 0 ? round(($totalAsistieron / $totalAsistencias) * 100, 2) : 0) . '%');

        $sheet->getStyle('A2:A6')->applyFromArray([
            'font' => ['bold' => true],
        ]);
        
        $sheet->getStyle('G2:G5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '0066CC']],
        ]);

        // Estilo para el encabezado de la tabla
        $headerRow = 7; // Fila donde están los encabezados ahora
        $sheet->getStyle("A{$headerRow}:M{$headerRow}")->applyFromArray([
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
        if ($lastRow > $headerRow) {
            $dataStartRow = $headerRow + 1;
            $sheet->getStyle("A{$dataStartRow}:M{$lastRow}")->applyFromArray([
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

            // Alternar colores de fila para datos
            for ($row = $dataStartRow; $row <= $lastRow; $row++) {
                if (($row - $dataStartRow) % 2 == 0) {
                    $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA'],
                        ],
                    ]);
                }
            }

            // Resaltar horas extra en color diferente
            $this->highlightExtraHours($sheet, $dataStartRow, $lastRow);
            
            // Resaltar diferentes fechas de tareo
            $this->highlightDifferentDates($sheet, $dataStartRow, $lastRow);
        }

        return [];
    }

    public function title(): string
    {
        return 'Reporte ' . $this->startDate->format('Y-m-d') . ' a ' . $this->endDate->format('Y-m-d');
    }

    /**
     * Obtiene todas las asistencias del proyecto en el rango de fechas
     */
    private function getProjectAttendances()
    {
        return Attendance::with(['employee', 'timesheet.employee', 'timesheet.project'])
            ->whereHas('timesheet', function ($query) {
                $query->where('project_id', $this->project->id)
                      ->whereBetween('check_in_date', [
                          $this->startDate->startOfDay(),
                          $this->endDate->endOfDay()
                      ]);
            })
            ->get()
            ->sortBy(function ($attendance) {
                // Ordenar por fecha del tareo y nombre del empleado
                $timesheetDate = $attendance->timesheet 
                    ? Carbon::parse($attendance->timesheet->check_in_date)->format('Y-m-d H:i') 
                    : '9999-12-31 23:59';
                $employeeName = $attendance->employee 
                    ? $attendance->employee->first_name . ' ' . $attendance->employee->last_name 
                    : 'ZZZ';
                return $timesheetDate . '_' . $employeeName;
            })
            ->values(); // Reindexar la colección
    }

    /**
     * Resalta las celdas de horas extra con un color especial
     */
    private function highlightExtraHours($sheet, $startRow, $endRow)
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $extraHoursValue = $sheet->getCell("L{$row}")->getValue();
            
            // Si hay horas extra (no es '0h 0m' ni está vacío)
            if ($extraHoursValue && $extraHoursValue !== '0h 0m' && $extraHoursValue !== '') {
                $sheet->getStyle("L{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFE6CC'], // Color naranja claro para horas extra
                    ],
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'CC6600'], // Color naranja oscuro para el texto
                    ],
                ]);
            }
        }
    }

    /**
     * Resalta diferentes fechas de tareo con colores alternos
     */
    private function highlightDifferentDates($sheet, $startRow, $endRow)
    {
        $currentDate = null;
        $colorIndex = 0;
        $colors = ['E8F4FD', 'FFF2CC']; // Azul claro y amarillo claro

        for ($row = $startRow; $row <= $endRow; $row++) {
            $dateValue = $sheet->getCell("A{$row}")->getValue();
            
            // Si cambia la fecha, cambiar el color
            if ($currentDate !== $dateValue) {
                $currentDate = $dateValue;
                $colorIndex = ($colorIndex + 1) % 2;
            }

            // Aplicar color de fondo a toda la fila para agrupar por fecha
            $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $colors[$colorIndex]],
                ],
            ]);
        }
    }
}