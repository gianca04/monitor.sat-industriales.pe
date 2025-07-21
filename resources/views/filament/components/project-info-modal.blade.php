{{-- Vista modal para mostrar información del cliente --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-3">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-building-office-2 class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Nombre del proyecot</p>
                    <p class="text-sm text-gray-900">{{ $project->name }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <x-heroicon-o-identification class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Fecha de inicio del proyecto</p>
                    <p class="text-sm text-gray-900">{{ $project->start_date }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <x-heroicon-o-user-group class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Fecha de finalización del proyecto</p>
                    <p class="text-sm text-gray-900">{{ $project->end_date }}</p>
                </div>
            </div>
        </div>
        
        <div class="space-y-3">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-phone class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Teléfono</p>
                    <p class="text-sm text-gray-900">{{ $project->contact_phone ?? 'No especificado' }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <x-heroicon-o-envelope class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Email</p>
                    <p class="text-sm text-gray-900">{{ $project->contact_email ?? 'No especificado' }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <x-heroicon-o-map-pin class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Dirección</p>
                    <p class="text-sm text-gray-900">{{ $project->address ?? 'No especificada' }}</p>
                </div>
            </div>
        </div>
    </div>
    
    @if($project->description)
    <div class="border-t pt-4">
        <div class="flex items-start space-x-2">
            <x-heroicon-o-document-text class="w-5 h-5 text-gray-500 mt-0.5" />
            <div>
                <p class="text-sm font-medium text-gray-700">Descripción</p>
                <p class="text-sm text-gray-900">{{ $project->description }}</p>
            </div>
        </div>
    </div>
    @endif
    
</div>
