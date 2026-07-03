<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Timesheet;
use App\Services\HoursCalculator;
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

class AttendancesExport implements FromCollection, WithHeadings, WithMapping, WithColumnWidths, WithStyles, WithTitle
{
    protected $timesheetId;
    protected $timesheet;

    public function __construct($timesheetId)
    {
        $this->timesheetId = $timesheetId;
        $this->timesheet = Timesheet::with(['project', 'attendances.employee'])->find($timesheetId);
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->timesheet->attendances()->with('employee')->get();
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

        return [
            $attendance->employee->document_number ?? '',
            $attendance->employee->first_name . ' ' . $attendance->employee->last_name,
            match($attendance->status) {
                'attended' => 'Asistió',
                'absent' => 'Faltó',
                'justified' => 'Justificado',
                'present' => 'Asistió',  // Mapear 'present' a 'Asistió'
                'late' => 'Llegó Tarde', // Mapear 'late' a 'Llegó Tarde'
                default => ucfirst($attendance->status ?? 'Sin definir')
            },
            match($attendance->shift) {
                'day' => 'Día',
                'night' => 'Noche',
                default => ucfirst($attendance->shift ?? 'No definido')
            },
            $attendance->check_in_date ? Carbon::parse($attendance->check_in_date)->format('d/m/Y H:i') : 'NO REGISTRADO',
            $attendance->break_date ? Carbon::parse($attendance->break_date)->format('d/m/Y H:i') : 'NO REGISTRADO',
            $attendance->end_break_date ? Carbon::parse($attendance->end_break_date)->format('d/m/Y H:i') : 'NO REGISTRADO',
            $attendance->check_out_date ? Carbon::parse($attendance->check_out_date)->format('d/m/Y H:i') : 'NO REGISTRADO',
            $horasTrabajadas,
            $horasExtra,
            $attendance->observation ?? ''
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
            'I' => 15, // Horas Trabajadas
            'J' => 15, // Horas Extra
            'K' => 30, // Observación
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Insertar información del tareo en la parte superior
        $sheet->insertNewRowBefore(1, 5);

        // Título principal
        $sheet->setCellValue('A1', 'REPORTE DE ASISTENCIAS - TAREO');
        $sheet->mergeCells('A1:K1');
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

        // Información del tareo
        $sheet->setCellValue('A2', 'Proyecto: ' . $this->timesheet->project->name);
        $sheet->setCellValue('A3', 'Fecha: ' . $this->timesheet->check_in_date->format('d/m/Y'));
        $sheet->setCellValue('A4', 'Supervisor: ' . ($this->timesheet->employee->first_name ?? 'N/A') . ' ' . ($this->timesheet->employee->last_name ?? ''));
        
        // Mostrar horario estándar del timesheet
        $horarioEstandar = '';
        if ($this->timesheet->check_in_date && $this->timesheet->check_out_date) {
            $checkInTime = Carbon::parse($this->timesheet->check_in_date)->format('H:i');
            $checkOutTime = Carbon::parse($this->timesheet->check_out_date)->format('H:i');
            $horarioEstandar = "{$checkInTime} - {$checkOutTime}";
            
            // Información de break del timesheet usando HoursCalculator
            $breakMinutes = HoursCalculator::calculateTimesheetBreakTime($this->timesheet);
            if ($breakMinutes > 0) {
                $breakFormatted = HoursCalculator::formatMinutesToHours($breakMinutes);
                if ($this->timesheet->break_date && $this->timesheet->end_break_date) {
                    $breakStart = Carbon::parse($this->timesheet->break_date)->format('H:i');
                    $breakEnd = Carbon::parse($this->timesheet->end_break_date)->format('H:i');
                    $horarioEstandar .= " (Break: {$breakStart}-{$breakEnd})";
                } else {
                    $horarioEstandar .= " (Break estándar: {$breakFormatted})";
                }
            }
        }
        $sheet->setCellValue('F2', 'Horario Estándar: ' . ($horarioEstandar ?: 'No definido'));
        
        $sheet->setCellValue('A5', 'Generado: ' . now()->format('d/m/Y H:i'));

        $sheet->getStyle('A2:A5')->applyFromArray([
            'font' => ['bold' => true],
        ]);
        
        $sheet->getStyle('F2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '0066CC']],
        ]);

        // Estilo para el encabezado de la tabla
        $headerRow = 6; // Fila donde están los encabezados ahora
        $sheet->getStyle("A{$headerRow}:K{$headerRow}")->applyFromArray([
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
            $sheet->getStyle("A{$dataStartRow}:K{$lastRow}")->applyFromArray([
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
                    $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F8F9FA'],
                        ],
                    ]);
                }
            }

            // Resaltar horas extra en color diferente
            $this->highlightExtraHours($sheet, $dataStartRow, $lastRow);
        }

        return [];
    }

    public function title(): string
    {
        return 'Asistencias ' . $this->timesheet->check_in_date->format('Y-m-d');
    }

    /**
     * Resalta las celdas de horas extra con un color especial
     */
    private function highlightExtraHours($sheet, $startRow, $endRow)
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            $extraHoursValue = $sheet->getCell("J{$row}")->getValue();
            
            // Si hay horas extra (no es '0h 0m' ni está vacío)
            if ($extraHoursValue && $extraHoursValue !== '0h 0m' && $extraHoursValue !== '') {
                $sheet->getStyle("J{$row}")->applyFromArray([
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
}
