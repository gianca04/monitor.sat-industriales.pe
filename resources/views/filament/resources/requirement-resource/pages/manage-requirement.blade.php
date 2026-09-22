<x-filament-panels::page>
    @vite(['resources/css/requirement.css'])
    <div class="space-y-6">
        <!-- Div 1: Ancho completo superior (Requirement Form) -->
        <div class="w-full">
            @include('filament.resources.requirement-resource.pages.partials.requirement-form')
        </div>

        <!-- Fila inferior responsive: Div 2 (1/4) y Div 3 (3/4) en Desktop -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            <!-- Div 2: 1/4 en desktop (1 columna de 4) - Catálogo / Buscador de Ítems -->
            <div class="lg:col-span-1">
                @include('filament.resources.requirement-resource.pages.partials.item-search')
            </div>

            <!-- Div 3: 3/4 en desktop (3 columnas de 4) -->
            <div
                class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 lg:col-span-3">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                    Div 3 (3/4)
                </h3>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    <!-- Contenido Div 3 (ej: Tabla de ítems agregados) -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modales Globales (Shadcn style) -->
    @include('filament.resources.requirement-resource.pages.partials.item-create-modal')
    @include('filament.resources.requirement-resource.pages.partials.category-subcategory-modal')
</x-filament-panels::page>
