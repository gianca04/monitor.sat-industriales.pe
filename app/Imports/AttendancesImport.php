<?php

namespace App\Imports;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;

class AttendancesImport implements ToCollection, WithHeadingRow, WithValidation
{
    use Importable;

    protected $timesheetId;
    protected $errors = [];
    protected $successCount = 0;

    public function __construct($timesheetId)
    {
        $this->timesheetId = $timesheetId;
    }

    public function collection(Collection $rows)
    {
        DB::beginTransaction();

        try {
            $timesheet = Timesheet::findOrFail($this->timesheetId);

            foreach ($rows as $index => $row) {
                try {
                    // Buscar empleado por documento o nombre completo
                    $employee = $this->findEmployee($row);

                    if (!$employee) {
                        $this->errors[] = "Fila " . ($index + 2) . ": No se encontró el empleado con documento '{$row['documento']}' o nombre '{$row['nombre_completo']}'";
                        continue;
                    }

                    // Verificar si ya existe una asistencia para este empleado en este tareo
                    $existingAttendance = Attendance::where('timesheet_id', $this->timesheetId)
                        ->where('employee_id', $employee->id)
                        ->first();

                    if ($existingAttendance) {
                        $this->errors[] = "Fila " . ($index + 2) . ": Ya existe una asistencia para {$employee->full_name} en este tareo";
                        continue;
                    }

                    // Crear la asistencia
                    $attendanceData = [
                        'timesheet_id' => $this->timesheetId,
                        'employee_id' => $employee->id,
                        'status' => $this->parseStatus($row['estado']),
                        'shift' => $this->parseShift($row['turno']),
                        'observation' => $row['observacion'] ?? null,
                    ];

                    // Solo agregar fechas si el estado es 'attended'
                    if ($attendanceData['status'] === 'attended') {
                        $attendanceData['check_in_date'] = $this->parseDateTime($row['fecha_entrada'], $timesheet->check_in_date);
                        $attendanceData['break_date'] = $this->parseDateTime($row['inicio_descanso']);
                        $attendanceData['end_break_date'] = $this->parseDateTime($row['fin_descanso']);
                        $attendanceData['check_out_date'] = $this->parseDateTime($row['fecha_salida'], $timesheet->check_out_date);
                    }

                    Attendance::create($attendanceData);
                    $this->successCount++;

                } catch (\Exception $e) {
                    $this->errors[] = "Fila " . ($index + 2) . ": Error al procesar - " . $e->getMessage();
                    Log::error("Error importing attendance row " . ($index + 2), [
                        'error' => $e->getMessage(),
                        'row' => $row->toArray()
                    ]);
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function findEmployee($row)
    {
        // Buscar por documento primero
        if (!empty($row['documento'])) {
            $employee = Employee::where('document_number', $row['documento'])->first();
            if ($employee) return $employee;
        }

        // Buscar por nombre completo
        if (!empty($row['nombre_completo'])) {
            $nameParts = explode(' ', trim($row['nombre_completo']));
            if (count($nameParts) >= 2) {
                $firstName = $nameParts[0];
                $lastName = implode(' ', array_slice($nameParts, 1));

                $employee = Employee::where('first_name', 'like', "%{$firstName}%")
                    ->where('last_name', 'like', "%{$lastName}%")
                    ->first();

                if ($employee) return $employee;
            }
        }

        return null;
    }

    private function parseStatus($status)
    {
        $status = strtolower(trim($status ?? ''));

        switch ($status) {
            case 'asistio':
            case 'asistió':
            case 'presente':
            case 'attended':
                return 'attended';
            case 'falto':
            case 'faltó':
            case 'ausente':
            case 'absent':
                return 'absent';
            case 'justificado':
            case 'justified':
                return 'justified';
            default:
                return 'attended'; // Por defecto
        }
    }

    private function parseShift($shift)
    {
        $shift = strtolower(trim($shift ?? ''));

        switch ($shift) {
            case 'dia':
            case 'día':
            case 'day':
                return 'day';
            case 'noche':
            case 'night':
                return 'night';
            default:
                return 'day'; // Por defecto
        }
    }

    private function parseDateTime($dateTimeString, $fallback = null)
    {
        if (empty($dateTimeString) || $dateTimeString === 'NO REGISTRADO') {
            return $fallback;
        }

        try {
            // Intentar varios formatos
            $formats = [
                'd/m/Y H:i',
                'd/m/Y H:i:s',
                'Y-m-d H:i:s',
                'Y-m-d H:i',
                'd-m-Y H:i',
                'd-m-Y H:i:s'
            ];

            foreach ($formats as $format) {
                $date = Carbon::createFromFormat($format, $dateTimeString);
                if ($date) {
                    return $date;
                }
            }

            // Si no funciona ningún formato, intentar parseo automático
            return Carbon::parse($dateTimeString);

        } catch (\Exception $e) {
            return $fallback;
        }
    }

    public function rules(): array
    {
        return [
            'documento' => 'nullable|string',
            'nombre_completo' => 'required|string',
            'estado' => 'required|string',
            'turno' => 'nullable|string',
        ];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }
}
