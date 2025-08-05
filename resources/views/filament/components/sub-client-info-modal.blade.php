{{-- Vista modal para mostrar información de la sede --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="space-y-3">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-building-office-2 class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Nombre de la Sede</p>
                    <p class="text-sm text-gray-900">{{ $subClient->name }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <x-heroicon-o-map-pin class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Ubicación</p>
                    <p class="text-sm text-gray-900">{{ $subClient->address ?? 'No especificada' }}</p>
                </div>

            </div>

            @if($subClient->latitude && $subClient->longitude)
            <div class="flex items-center space-x-2">
                <x-heroicon-o-globe-americas class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Coordenadas</p>
                    <p class="text-sm text-gray-900">Lat: {{ $subClient->latitude }}, Lng: {{ $subClient->longitude }}</p>
                </div>
            </div>
            @endif
        </div>

        <div class="space-y-3">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-briefcase class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Cliente Principal</p>
                    <p class="text-sm text-gray-900">{{ $subClient->client->business_name }}</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <x-heroicon-o-identification class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Documento del Cliente</p>
                    <p class="text-sm text-gray-900">{{ $subClient->client->document_type }} - {{ $subClient->client->document_number }}</p>
                </div>
            </div>

            @if($subClient->client->contact_phone)
            <div class="flex items-center space-x-2">
                <x-heroicon-o-phone class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Teléfono del Cliente</p>
                    <p class="text-sm text-gray-900">{{ $subClient->client->contact_phone }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    @if($subClient->description)
    <div class="pt-4 border-t">
        <div class="flex items-start space-x-2">
            <x-heroicon-o-document-text class="w-5 h-5 text-gray-500 mt-0.5" />
            <div>
                <p class="text-sm font-medium text-gray-700">Descripción de la Sede</p>
                <p class="text-sm text-gray-900">{{ $subClient->description }}</p>
            </div>
        </div>
    </div>
    @endif

    <div class="pt-4 border-t">
        <div class="flex items-center mb-2 space-x-2">
            <x-heroicon-o-calendar class="w-5 h-5 text-gray-500" />
            <h4 class="text-sm font-medium text-gray-700">Información de Registro</h4>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-600">Creado:</p>
                <p class="text-gray-900">{{ $subClient->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p class="text-gray-600">Actualizado:</p>
                <p class="text-gray-900">{{ $subClient->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>
