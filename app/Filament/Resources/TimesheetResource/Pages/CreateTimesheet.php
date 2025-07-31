<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Filament\Resources\TimesheetResource;
use App\Models\Timesheet;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class CreateTimesheet extends CreateRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Validar antes de crear el registro
        $existingTimesheet = Timesheet::where('project_id', $data['project_id'])
            ->whereDate('check_in_date', Carbon::parse($data['check_in_date'])->toDateString())
            ->first();

        if ($existingTimesheet) {
            Notification::make()
                ->title('Error al crear tareo')
                ->body('Ya existe un tareo para este proyecto en la fecha seleccionada. No se puede crear un tareo duplicado.')
                ->danger()
                ->persistent()
                ->send();

            $this->halt();
        }

        return static::getModel()::create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', [
            'record' => $this->getRecord(),
        ]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Tareo creado exitosamente';
    }
}
