<?php

namespace App\Filament\Resources\TimesheetResource\Pages;

use App\Exports\AttendancesExport;
use App\Filament\Resources\TimesheetResource;
use App\Filament\Resources\TimesheetResource\Widgets\TimesheetStatsWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Exports\AttendanceTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class ViewTimesheet extends ViewRecord
{
    protected static string $resource = TimesheetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /*Actions\Action::make('downloadTemplate')
                ->label('Descargar Plantilla Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $timesheet = $this->record;

                    return Excel::download(
                        new AttendanceTemplateExport($timesheet->project_id, $timesheet->check_in_date),
                        'plantilla_asistencias_' . $timesheet->project->name . '_' . $timesheet->check_in_date->format('Y-m-d') . '.xlsx'
                    );
                }),
                */

            Actions\Action::make('exportAttendances')
                ->label('Exportar Asistencias')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action(function () {
                    // Implementar export de asistencias actuales
                    $timesheet = $this->record;

                    return Excel::download(
                        new AttendancesExport($timesheet->id),
                        'asistencias_' . $timesheet->project->name . '_' . $timesheet->check_in_date->format('Y-m-d') . '.xlsx'
                    );
                })
                ->visible(fn() => $this->record->attendances()->count() > 0),

            Actions\EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TimesheetStatsWidget::class,
        ];
    }
}
