<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    public function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('crearProyecto')
                ->label('Crear Proyecto')
                ->icon('heroicon-o-bolt')
                ->visible(fn($record) => $record->status === 'accepted')
                ->action(function ($record) {
                    session()->flash('quote_id', $record->id);
                    Notification::make()
                        ->title('ID de cotización guardada en sesión.')
                        ->success()
                        ->send();
                    return redirect('/projects/create'); // Cambia esta URL si tu panel usa otra ruta
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('crearProyecto')
                ->label('Crear Proyecto')
                ->icon('heroicon-o-puzzle-piece')
                ->visible(fn() => $this->record->status === 'accepted')
                ->action(function () {
                    session()->flash('quote_id', value: $this->record->id);
                    Notification::make()
                        ->title('Cotización transferida.')
                        ->success()
                        ->send();
                    return redirect('/dashboard/projects/create'); // Cambia esta URL si tu panel usa otra ruta
                }),

            Actions\DeleteAction::make(),
        ];
    }
}
