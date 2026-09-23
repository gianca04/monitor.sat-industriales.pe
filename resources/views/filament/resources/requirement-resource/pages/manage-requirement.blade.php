<x-filament-panels::page>
    @vite(['resources/css/requirement.css'])
    <div class="space-y-4">
        <!-- Div 1: Ancho completo superior (Requirement Form) -->
        <div class="w-full">
            @include('filament.resources.requirement-resource.pages.partials.requirement-form')
        </div>

        <!-- Fila inferior responsive: Div 2 (1/4) y Div 3 (3/4) en Desktop -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">
            <!-- Div 2: 1/4 en desktop (1 columna de 4) - Catálogo / Buscador de Ítems -->
            <div class="lg:col-span-1">
                @include('filament.resources.requirement-resource.pages.partials.item-search')
            </div>

            <!-- Div 3: 3/4 en desktop (3 columnas de 4) - Lista y tabla de ítems -->
            <div class="lg:col-span-3">
                @include('filament.resources.requirement-resource.pages.partials.item-list')
            </div>
        </div>
    </div>

    <!-- Modales Globales (Shadcn style) -->
    @include('filament.resources.requirement-resource.pages.partials.item-create-modal')
    @include('filament.resources.requirement-resource.pages.partials.category-subcategory-modal')
    @include('filament.resources.requirement-resource.pages.partials.confirm-modal')
</x-filament-panels::page>
