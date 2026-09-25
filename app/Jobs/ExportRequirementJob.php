<?php

namespace App\Jobs;

use App\Models\Requirement;
use App\Models\User;
use App\Services\RequirementExportService;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExportRequirementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600; // 10 minutes max for large exports

    public function __construct(
        public int $userId,
        public int $requirementId,
        public array $requirementListIds = []
    ) {}

    public function handle(RequirementExportService $exportService): void
    {
        $user = User::find($this->userId);
        $requirement = Requirement::find($this->requirementId);

        if (! $user || ! $requirement) {
            return;
        }

        try {
            // Generar el Excel temporalmente
            if (empty($this->requirementListIds)) {
                $tempPath = $exportService->exportAll($requirement);
            } else {
                $items = $requirement->requirementLists()->whereIn('id', $this->requirementListIds)->with(['item', 'item.unit'])->get();
                $tempPath = $exportService->export($requirement, $items);
            }

            // Mover el archivo a un storage persistente y protegido
            $filename = 'exports/REQ_'.$requirement->id.'_'.Str::random(10).'.xlsx';

            // Asegurarnos de que el directorio existe
            Storage::disk('local')->makeDirectory('exports');
            Storage::disk('local')->put($filename, file_get_contents($tempPath));

            // Eliminar el temporal
            @unlink($tempPath);

            // Enviar notificación a Filament
            $downloadUrl = route('download.export', ['path' => base64_encode($filename)]);

            Notification::make()
                ->title("Exportación de Requerimiento #{$requirement->id} lista")
                ->success()
                ->body('El archivo Excel ha sido generado exitosamente.')
                ->actions([
                    Action::make('descargar')
                        ->label('Descargar Excel')
                        ->url($downloadUrl)
                        ->button(),
                ])
                ->sendToDatabase($user);

        } catch (\Exception $e) {
            // En caso de error, notificar al usuario
            Notification::make()
                ->title('Error en exportación')
                ->danger()
                ->body("No se pudo exportar el Requerimiento #{$requirement->id}: ".$e->getMessage())
                ->sendToDatabase($user);
        }
    }
}
