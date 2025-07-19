{{-- Vista modal para mostrar información del empleado/cotizador --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="space-y-3">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-user class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Nombre Completo</p>
                    <p class="text-sm text-gray-900">{{ $employee->full_name }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <x-heroicon-o-identification class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Documento</p>
                    <p class="text-sm text-gray-900">{{ $employee->document_type }} - {{ $employee->document_number }}</p>
                </div>
            </div>
            
            @if($employee->address)
            <div class="flex items-center space-x-2">
                <x-heroicon-o-map-pin class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Dirección</p>
                    <p class="text-sm text-gray-900">{{ $employee->address }}</p>
                </div>
            </div>
            @endif
            
            @if($employee->date_contract)
            <div class="flex items-center space-x-2">
                <x-heroicon-o-calendar class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Fecha de Contrato</p>
                    <p class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($employee->date_contract)->format('d/m/Y') }}</p>
                </div>
            </div>
            @endif
        </div>
        
        <div class="space-y-3">
            @if($employee->user)
            <div class="flex items-center space-x-2">
                <x-heroicon-o-envelope class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Correo Electrónico</p>
                    <p class="text-sm text-gray-900">{{ $employee->user->email }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <x-heroicon-o-shield-check class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Estado del Usuario</p>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $employee->user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $employee->user->is_active ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>
            </div>
            
            @if($employee->user->roles->count() > 0)
            <div class="flex items-start space-x-2">
                <x-heroicon-o-user-group class="w-5 h-5 text-gray-500 mt-0.5" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Roles</p>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @foreach($employee->user->roles as $role)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $role->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
            @else
            <div class="flex items-center space-x-2">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-500" />
                <div>
                    <p class="text-sm font-medium text-yellow-700">Sin Usuario Asociado</p>
                    <p class="text-sm text-yellow-600">Este empleado no tiene cuenta de usuario</p>
                </div>
            </div>
            @endif
        </div>
    </div>
    
    @if($employee->phone || $employee->emergency_contact)
    <div class="border-t pt-4">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Información de Contacto</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($employee->phone)
            <div class="flex items-center space-x-2">
                <x-heroicon-o-phone class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Teléfono</p>
                    <p class="text-sm text-gray-900">{{ $employee->phone }}</p>
                </div>
            </div>
            @endif
            
            @if($employee->emergency_contact)
            <div class="flex items-center space-x-2">
                <x-heroicon-o-exclamation-circle class="w-5 h-5 text-gray-500" />
                <div>
                    <p class="text-sm font-medium text-gray-700">Contacto de Emergencia</p>
                    <p class="text-sm text-gray-900">{{ $employee->emergency_contact }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
    
    <div class="border-t pt-4">
        <div class="flex items-center space-x-2 mb-2">
            <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
            <h4 class="text-sm font-medium text-gray-700">Información de Registro</h4>
        </div>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-gray-600">Creado:</p>
                <p class="text-gray-900">{{ $employee->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div>
                <p class="text-gray-600">Actualizado:</p>
                <p class="text-gray-900">{{ $employee->updated_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>
