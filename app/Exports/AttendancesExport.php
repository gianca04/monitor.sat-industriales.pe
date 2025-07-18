<?php

namespace App\Exports;

use App\Models\Attendance;
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
        $horasTrabajadas = 'NO CALCULADO';
        $horasExtra = '0h 0m';

        // Verificar que existan los datos básicos
        if ($attendance->check_in_date && $attendance->check_out_date) {
            $checkIn = Carbon::parse($attendance->check_in_date);
            $checkOut = Carbon::parse($attendance->check_out_date);

            // Calcular tiempo total en minutos
            $totalMinutes = $checkIn->diffInMinutes($checkOut);

            // Calcular tiempo de break en minutos del empleado
            $breakTime = 0;
            if ($attendance->break_date && $attendance->end_break_date) {
                $breakStart = Carbon::parse($attendance->break_date);
                $breakEnd = Carbon::parse($attendance->end_break_date);
                $breakTime = $breakStart->diffInMinutes($breakEnd);
            }

            // Tiempo trabajado = tiempo total - tiempo de break
            $workedMinutes = max(0, $totalMinutes - $breakTime);
            
            // Convertir a horas y minutos trabajadas
            if ($workedMinutes > 0) {
                $hours = intval($workedMinutes / 60);
                $minutes = $workedMinutes % 60;
                $horasTrabajadas = "{$hours}h {$minutes}m";
            } else {
                $horasTrabajadas = "0h 0m";
            }

            // Calcular horas extra basado en el horario del timesheet
            $horasExtra = $this->calculateExtraHours($attendance, $workedMinutes);
        }

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
            
            // Debug: Agregar información de break del timesheet
            if ($this->timesheet->break_date && $this->timesheet->end_break_date) {
                $breakStart = Carbon::parse($this->timesheet->break_date)->format('H:i');
                $breakEnd = Carbon::parse($this->timesheet->end_break_date)->format('H:i');
                $horarioEstandar .= " (Break: {$breakStart}-{$breakEnd})";
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
     * Calcula las horas extra trabajadas por un empleado
     * comparando con el horario establecido en el timesheet
     */
    private function calculateExtraHours($attendance, $totalWorkedMinutes)
    {
        // Validaciones básicas
        if (!$attendance->check_in_date || !$attendance->check_out_date || !$this->timesheet) {
            return '0h 0m';
        }

        if ($totalWorkedMinutes <= 0) {
            return '0h 0m';
        }

        // Obtener horarios del timesheet (horario estándar)
        $timesheetCheckIn = $this->timesheet->check_in_date ? Carbon::parse($this->timesheet->check_in_date) : null;
        $timesheetCheckOut = $this->timesheet->check_out_date ? Carbon::parse($this->timesheet->check_out_date) : null;

        if (!$timesheetCheckIn || !$timesheetCheckOut) {
            return '0h 0m';
        }

        // Calcular tiempo total del horario estándar
        $standardTotalMinutes = $timesheetCheckIn->diffInMinutes($timesheetCheckOut);

        // Calcular tiempo de break del timesheet estándar
        $timesheetBreakTime = 0;
        if ($this->timesheet->break_date && $this->timesheet->end_break_date) {
            $timesheetBreakStart = Carbon::parse($this->timesheet->break_date);
            $timesheetBreakEnd = Carbon::parse($this->timesheet->end_break_date);
            $timesheetBreakTime = $timesheetBreakStart->diffInMinutes($timesheetBreakEnd);
        } else {
            // Si el timesheet no tiene break configurado, pero los empleados sí,
            // asumimos un break estándar de 1 hora (60 minutos)
            // Esto es común en muchas empresas
            $timesheetBreakTime = 60;
        }

        // Minutos de trabajo estándar según el timesheet (total - break)
        $standardWorkMinutes = max(0, $standardTotalMinutes - $timesheetBreakTime);

        // Si no hay horario estándar definido, no hay horas extra
        if ($standardWorkMinutes <= 0) {
            return '0h 0m';
        }

        // Calcular horas extra: diferencia entre tiempo trabajado y tiempo estándar
        $extraMinutes = max(0, $totalWorkedMinutes - $standardWorkMinutes);

        if ($extraMinutes > 0) {
            $extraHours = intval($extraMinutes / 60);
            $extraMinutesRemainder = $extraMinutes % 60;
            return "{$extraHours}h {$extraMinutesRemainder}m";
        }

        return '0h 0m';
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
