<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class ubicacion extends Field
{
    protected string $view = 'forms.components.ubicacion';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dehydrateStateUsing(function ($state) {
            // Asegurar que siempre se guarde como objeto JSON válido
            if (is_string($state)) {
                $decoded = json_decode($state, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

            if (is_array($state)) {
                return $state;
            }

            // Valor por defecto para Lima, Perú
            return [
                'latitude' => -12.046374,
                'longitude' => -77.042793,
                'location' => ''
            ];
        });

        $this->mutateDehydratedStateUsing(function ($state) {
            // Asegurar que el estado se almacena correctamente
            if (is_array($state)) {
                return [
                    'latitude' => (float) ($state['latitude'] ?? -12.046374),
                    'longitude' => (float) ($state['longitude'] ?? -77.042793),
                    'location' => (string) ($state['location'] ?? '')
                ];
            }

            return $state;
        });
    }
}
