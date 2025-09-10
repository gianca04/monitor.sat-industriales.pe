{{-- Vista modal para mostrar información del cliente --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="space-y-3">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-building-office-2 class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Razón Social</p>
                    <p class="text-sm text-gray-900">{{ $client->business_name }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <x-heroicon-o-identification class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Documento</p>
                    <p class="text-sm text-gray-900">{{ $client->document_type }} - {{ $client->document_number }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <x-heroicon-o-user-group class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Tipo de Persona</p>
                    <p class="text-sm text-gray-900">{{ $client->person_type }}</p>
                </div>
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-phone class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Teléfono</p>
                    <p class="text-sm text-gray-900">{{ $client->contact_phone ?? 'No especificado' }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <x-heroicon-o-envelope class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Email</p>
                    <p class="text-sm text-gray-900">{{ $client->contact_email ?? 'No especificado' }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <x-heroicon-o-map-pin class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Dirección</p>
                    <p class="text-sm text-gray-900">{{ $client->address ?? 'No especificada' }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($client->description)
    <div class="pt-4 border-t">
        <div class="flex items-start space-x-2">
            <x-heroicon-o-document-text class="w-5 h-5 text-gray-500 mt-0.5" />
            <div>
                <p class="text-sm font-medium text-gray-700">Descripción</p>
                <p class="text-sm text-gray-900">{{ $client->description }}</p>
            </div>
        </div>
    </div>
    @endif

    @if($client->subClients && $client->subClients->count() > 0)
    <div class="pt-4 border-t">
        <h4 class="mb-2 text-sm font-medium text-gray-700">Sedes ({{ $client->subClients->count() }})</h4>
        <div class="space-y-2">
            @foreach($client->subClients as $subClient)
            <div class="flex items-center p-2 space-x-2 rounded bg-gray-50">
                <x-heroicon-o-home-modern class="w-4 h-4 text-gray-500" />
                <span class="text-sm">{{ $subClient->name }} - {{ $subClient->location['location'] ?? 'No especificada' }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
