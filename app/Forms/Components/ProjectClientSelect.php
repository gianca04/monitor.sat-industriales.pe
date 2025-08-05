<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Split;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action as FormAction;
use App\Models\Client;
use App\Models\SubClient;

class ProjectClientSelect
{
    public static function make(): Split
    {
        return Split::make([
            Section::make([
                Select::make('client_id')
                    ->required()
                    ->prefixIcon('heroicon-m-briefcase')
                    ->label('Cliente')
                    ->options(function (callable $get) {
                        return Client::query()
                            ->select('id', 'business_name', 'document_number')
                            ->when($get('search'), function ($query, $search) {
                                $query->where('business_name', 'like', "%{$search}%")
                                    ->orWhere('document_number', 'like', "%{$search}%");
                            })
                            ->get()
                            ->mapWithKeys(function ($client) {
                                return [$client->id => $client->business_name . ' - ' . $client->document_number];
                            })
                            ->toArray();
                    })
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(fn($state, callable $set) => $set('sub_client_id', null))
                    ->helperText('Selecciona el cliente para este proyecto.')
                    ->suffixAction(
                        FormAction::make('view_client')
                            ->icon('heroicon-o-eye')
                            ->tooltip('Ver información del cliente')
                            ->color('info')
                            ->action(function (callable $get) {
                                $clientId = $get('client_id');
                                if (!$clientId) {
                                    Notification::make()
                                        ->title('Selecciona un cliente primero')
                                        ->warning()
                                        ->send();
                                    return;
                                }
                            })
                            ->modalContent(function (callable $get) {
                                $clientId = $get('client_id');
                                if (!$clientId) return null;
                                $client = Client::with('subClients')->find($clientId);
                                if (!$client) return null;
                                return view('filament.components.client-info-modal', compact('client'));
                            })
                            ->modalHeading('Información del Cliente')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Cerrar')
                            ->modalWidth('2xl')
                            ->visible(fn(callable $get) => !empty($get('client_id')))
                    ),
            ]),
            Section::make([
                Select::make('sub_client_id')
                    ->required()
                    ->prefixIcon('heroicon-m-home-modern')
                    ->label('Sede')
                    ->options(function (callable $get) {
                        $clientId = $get('client_id');
                        return SubClient::where('client_id', $clientId)
                            ->get()
                            ->mapWithKeys(function ($subClient) {
                                return [$subClient->id => $subClient->name];
                            })
                            ->toArray();
                    })
                    ->reactive()
                    ->searchable()
                    ->disabled(fn($get) => !$get('client_id'))
                    ->helperText('Selecciona el Sede para este proyecto.')
                    ->afterStateHydrated(function ($state, callable $set) {
                        if ($state) {
                            $subClient = SubClient::find($state);
                            if ($subClient) {
                                $set('client_id', $subClient->client_id);
                            }
                        }
                    })
                    ->suffixAction(
                        FormAction::make('view_sub_client')
                            ->icon('heroicon-o-eye')
                            ->tooltip('Ver información de la sede')
                            ->color('info')
                            ->action(function (callable $get) {
                                $subClientId = $get('sub_client_id');
                                if (!$subClientId) {
                                    Notification::make()
                                        ->title('Selecciona una sede primero')
                                        ->warning()
                                        ->send();
                                    return;
                                }
                            })
                            ->modalContent(function (callable $get) {
                                $subClientId = $get('sub_client_id');
                                if (!$subClientId) return null;
                                $subClient = SubClient::with('client')->find($subClientId);
                                if (!$subClient) return null;
                                return view('filament.components.sub-client-info-modal', compact('subClient'));
                            })
                            ->modalHeading('Información de la Sede')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Cerrar')
                            ->modalWidth('2xl')
                            ->visible(fn(callable $get) => !empty($get('sub_client_id')))
                    ),
            ]),
        ])
        ->from('md')
        ->columnSpanFull();
    }
}
