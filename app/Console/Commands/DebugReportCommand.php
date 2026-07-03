<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\Attendance;
use Carbon\Carbon;

class DebugReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug data for attendance reports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== ANÁLISIS DE DATOS PARA REPORTE ===');
        $this->newLine();

        // Obtener todos los proyectos
        $projects = Project::with(['timesheets.attendances.employee'])->get();

        $this->info("Total de proyectos: " . $projects->count());
        $this->newLine();

        foreach ($projects as $project) {
            $this->info("PROYECTO: " . $project->name . " (ID: " . $project->id . ")");
            $this->line("  - Timesheets: " . $project->timesheets->count());
            
            $totalAttendances = 0;
            foreach ($project->timesheets as $timesheet) {
                $attendanceCount = $timesheet->attendances->count();
                $totalAttendances += $attendanceCount;
                
                if ($attendanceCount > 0) {
                    $this->line("    - Timesheet " . $timesheet->id . " (" . 
                             Carbon::parse($timesheet->check_in_date)->format('Y-m-d') . 
                             "): " . $attendanceCount . " asistencias");
                }
            }
            
            $this->line("  - Total asistencias: " . $totalAttendances);
            
            // Probar la relación hasManyThrough
            $attendancesThroughRelation = $project->attendances()->count();
            $this->line("  - Asistencias via relación: " . $attendancesThroughRelation);
            
            $this->newLine();
        }

        // Probar consulta específica del export
        $this->info('=== PRUEBA DE CONSULTA ESPECÍFICA ===');

        if ($projects->count() > 0) {
            $project = $projects->first();
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
            
            $this->info("Probando proyecto: " . $project->name);
            $this->info("Rango de fechas: " . $startDate->format('Y-m-d') . " a " . $endDate->format('Y-m-d'));
            
            $attendances = Attendance::with(['employee', 'timesheet.employee', 'timesheet.project'])
                ->whereHas('timesheet', function ($query) use ($project, $startDate, $endDate) {
                    $query->where('project_id', $project->id)
                          ->whereBetween('check_in_date', [
                              $startDate->startOfDay(),
                              $endDate->endOfDay()
                          ]);
                })
                ->get();
            
            $this->info("Asistencias encontradas: " . $attendances->count());
            
            if ($attendances->count() > 0) {
                $this->newLine();
                $this->info("Primeras 3 asistencias:");
                foreach ($attendances->take(3) as $attendance) {
                    $this->line("  - " . ($attendance->employee->first_name ?? 'Sin nombre') . " " . 
                             ($attendance->employee->last_name ?? '') . 
                             " (" . ($attendance->status ?? 'Sin estado') . ") - " .
                             "Timesheet: " . ($attendance->timesheet->id ?? 'Sin timesheet'));
                }
            }
        }

        $this->newLine();
        $this->info('Análisis completado.');

        return Command::SUCCESS;
    }
}
