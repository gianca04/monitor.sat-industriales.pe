<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateTimesheetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timesheet:generate {project_id?} {--date=} {--dry-run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera tareos automáticamente para proyectos activos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectId = $this->argument('project_id');
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $dryRun = $this->option('dry-run');

        $this->info("🚀 Generando tareos para la fecha: {$date->format('d/m/Y')}");

        if ($dryRun) {
            $this->warn("⚠️  Modo DRY RUN activado - No se realizarán cambios reales");
        }

        // Obtener proyectos
        $projects = $projectId
            ? Project::where('id', $projectId)->get()
            : Project::all(); // Aquí podrías agregar filtros para proyectos activos

        if ($projects->isEmpty()) {
            $this->error('❌ No se encontraron proyectos');
            return 1;
        }

        $generated = 0;
        $skipped = 0;

        foreach ($projects as $project) {
            // Verificar si ya existe un tareo para este proyecto en esta fecha
            $existingTimesheet = Timesheet::where('project_id', $project->id)
                ->whereDate('check_in_date', $date->toDateString())
                ->first();

            if ($existingTimesheet) {
                $this->line("⏭️  Saltando {$project->name} - Ya existe tareo para esta fecha");
                $skipped++;
                continue;
            }

            // Crear el tareo
            $timesheetData = [
                'project_id' => $project->id,
                'employee_id' => 1, // Empleado supervisor por defecto - ajustar según tu lógica
                'shift' => 'day',
                'check_in_date' => $date->copy()->setTime(8, 0), // 8:00 AM
                'break_date' => $date->copy()->setTime(12, 0),   // 12:00 PM
                'end_break_date' => $date->copy()->setTime(13, 0), // 1:00 PM
                'check_out_date' => $date->copy()->setTime(17, 0), // 5:00 PM
            ];

            if (!$dryRun) {
                try {
                    Timesheet::create($timesheetData);
                    $this->line("✅ Tareo creado para: {$project->name}");
                    $generated++;
                } catch (\Exception $e) {
                    $this->error("❌ Error creando tareo para {$project->name}: {$e->getMessage()}");
                }
            } else {
                $this->line("🔍 [DRY RUN] Se crearía tareo para: {$project->name}");
                $generated++;
            }
        }

        $this->newLine();
        $this->info("📊 Resumen:");
        $this->line("   • Tareos generados: {$generated}");
        $this->line("   • Tareos saltados: {$skipped}");
        $this->line("   • Total proyectos procesados: " . ($generated + $skipped));

        if ($dryRun) {
            $this->warn("⚠️  Para ejecutar realmente, quite la opción --dry-run");
        }

        return 0;
    }
}
