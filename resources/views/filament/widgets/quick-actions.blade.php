<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Acciones Rápidas
        </x-slot>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <!-- Crear Nuevo Tareo -->
            <div class="p-4 transition-colors bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-primary-300"
                onclick="window.location.href='{{ route('filament.dashboard.resources.timesheets.create') }}'">>
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-primary-100">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-sm font-medium text-gray-900">Nuevo Tareo</h3>
                        <p class="text-xs text-gray-500">Crear tareo de hoy</p>
                    </div>
                </div>
            </div>


        </div>

        <!-- Estadísticas de Hoy -->
        <div class="p-4 mt-6 rounded-lg bg-gray-50">
            <h4 class="mb-2 text-sm font-medium text-gray-900">Resumen de Hoy</h4>
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
                {{ $stats['today_timesheets'] }} tareos registrados hoy
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
