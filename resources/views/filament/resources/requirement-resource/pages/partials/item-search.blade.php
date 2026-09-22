<div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950 flex flex-col h-full"
    x-data="{
        search: '',
        items: [],
        loading: false,
        hasSearched: false,
        async fetchItems() {
            this.loading = true;
            try {
                const params = new URLSearchParams();
                if (this.search && this.search.trim().length > 0) {
                    params.append('search', this.search.trim());
                }
                params.append('per_page', '12');
    
                const response = await fetch(`{{ route('items.search') }}?${params.toString()}`);
                if (response.ok) {
                    const res = await response.json();
                    this.items = res.data || [];
                    this.hasSearched = true;
                }
            } catch (error) {
                console.error('Error buscando ítems:', error);
            } finally {
                this.loading = false;
            }
        },
        clearSearch() {
            this.search = '';
            this.fetchItems();
        },
        init() {
            this.fetchItems();
        }
    }" @item-created.window="fetchItems()">
    <!-- Card Header -->
    <div class="flex items-center justify-between p-4 border-b border-zinc-100 dark:border-zinc-900">
        <div class="flex items-center gap-2">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 leading-none">
                    Catálogo de Ítems
                </h3>
                <p class="text-[11px] text-zinc-400 mt-1">
                    Materiales y suministros
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="$dispatch('open-item-modal', { name: search })"
                class="inline-flex items-center gap-1 text-[11px] font-medium bg-zinc-900 text-zinc-50 dark:bg-zinc-50 dark:text-zinc-900 rounded-md px-2 py-1 shadow-sm hover:opacity-90 transition-opacity"
                title="Crear nuevo ítem">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span>Nuevo</span>
            </button>
        </div>
    </div>

    <!-- Barra de búsqueda superior -->
    <div class="p-3 border-b border-zinc-100 dark:border-zinc-900 bg-zinc-50/50 dark:bg-zinc-900/20">
        <div class="relative">
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            <input type="text" x-model="search" @input.debounce.300ms="fetchItems()"
                placeholder="Buscar por nombre o SKU..." class="shadcn-input !h-9 !text-xs !pl-8 !pr-8"
                autocomplete="off" />

            <!-- Botón de limpiar o Spinner -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-2.5">
                <template x-if="loading">
                    <svg class="animate-spin h-3.5 w-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg"
                        fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                </template>
                <template x-if="!loading && search.length > 0">
                    <button type="button" @click="clearSearch()"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors p-0.5 rounded"
                        title="Limpiar búsqueda">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </template>
            </div>
        </div>
    </div>

    <!-- Lista de resultados de ítems -->
    <div class="flex-1 overflow-y-auto max-h-[520px] p-2 space-y-1.5">
        <!-- Skeleton loader -->
        <template x-if="loading && items.length === 0">
            <div class="space-y-2 p-1">
                <div class="h-16 rounded-lg bg-zinc-100 dark:bg-zinc-900 animate-pulse"></div>
                <div class="h-16 rounded-lg bg-zinc-100 dark:bg-zinc-900 animate-pulse"></div>
                <div class="h-16 rounded-lg bg-zinc-100 dark:bg-zinc-900 animate-pulse"></div>
            </div>
        </template>

        <!-- Tarjetas de ítems -->
        <template x-for="item in items" :key="item.id">
            <div @click="$dispatch('item-selected', item)"
                class="group rounded-lg border border-zinc-200/80 bg-zinc-50/40 p-2.5 transition-all hover:bg-zinc-100/70 hover:border-zinc-300 dark:border-zinc-800/80 dark:bg-zinc-900/30 dark:hover:bg-zinc-900/80 dark:hover:border-zinc-700 cursor-pointer flex flex-col gap-1.5">
                <div class="flex items-center justify-between gap-1.5">
                    <!-- SKU Badge -->
                    <span
                        class="text-[10px] font-semibold text-zinc-600 dark:text-zinc-300 bg-zinc-200/60 dark:bg-zinc-800 px-1.5 py-0.5 rounded"
                        x-text="item.sku"></span>

                    <!-- Unidad de medida -->
                    <span class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400"
                        x-text="item.unit ? (item.unit.symbol || item.unit.name) : 'UND'"></span>
                </div>

                <!-- Nombre del Ítem -->
                <h4 class="text-xs font-medium text-zinc-900 dark:text-zinc-100 leading-snug line-clamp-2"
                    x-text="item.name"></h4>

                <!-- Subcategoría e ícono de acción -->
                <div class="flex items-center justify-between pt-0.5 text-[10px] text-zinc-400 dark:text-zinc-500">
                    <span class="truncate" x-text="item.subcategory?.name || 'General'"></span>
                    <span
                        class="opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-0.5 text-zinc-600 dark:text-zinc-300 font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </span>
                </div>
            </div>
        </template>

        <!-- Estado vacío: Sin resultados -->
        <template x-if="!loading && items.length === 0">
            <div class="py-8 text-center px-4">
                <div
                    class="mx-auto flex h-9 w-9 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900 text-zinc-400 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300">
                    No se encontraron ítems
                </p>
                <p class="text-[11px] text-zinc-400 mt-0.5" x-show="search">
                    No hay resultados para "<span x-text="search"></span>"
                </p>
                <button type="button" @click="$dispatch('open-item-modal', { name: search })"
                    class="mt-3 inline-flex items-center gap-1 text-[11px] font-medium text-zinc-900 dark:text-zinc-100 bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 px-2.5 py-1 rounded-md transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Crear nuevo ítem</span>
                </button>
            </div>
        </template>
    </div>
</div>
