<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use App\Filament\Resources\TimesheetResource\Widgets\TimesheetStatsWidget;
use App\Models\Timesheet;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditTimesheet extends EditRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /*Actions\Action::make('generateAttendances')
                ->label('Generar Asistencias Automáticas')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->action(function () {
                    $timesheet = $this->record;
                    $project = $timesheet->project;

                    // Aquí podrías implementar lógica para generar asistencias automáticamente
                    // basándose en empleados asignados al proyecto o una lista predefinida

                    Notification::make()
                        ->title('Funcionalidad en desarrollo')
                        ->body('Esta funcionalidad estará disponible próximamente para generar asistencias automáticamente basándose en empleados del proyecto.')
                        ->warning()
                        ->send();
                })
                ->visible(fn() => $this->record->attendances()->count() === 0),

                */
            Actions\DeleteAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TimesheetStatsWidget::class,
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Validar antes de actualizar el registro
        $existingTimesheet = Timesheet::where('project_id', $data['project_id'])
            ->whereDate('check_in_date', Carbon::parse($data['check_in_date'])->toDateString())
            ->where('id', '!=', $record->id)
            ->first();

        if ($existingTimesheet) {
            Notification::make()
                ->title('Error al actualizar tareo')
                ->body('Ya existe un tareo para este proyecto en la fecha seleccionada. No se puede actualizar a una fecha que ya tiene tareo.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        $record->update($data);
        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Tareo actualizado exitosamente';
    }
}
