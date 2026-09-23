<div
    class="rounded-xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800/80 dark:bg-zinc-950 overflow-hidden">
    <!-- Header compacto integrado: Título + Badge + Botón Guardar -->
    <div
        class="flex items-center justify-between px-4 py-2.5 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-900/30">
        <div class="flex items-center gap-2.5">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 leading-none">
                    Datos del Requerimiento
                </h3>
                @if (isset($record) && $record)
                    <span
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200/60 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800/60">
                        Edición #{{ $record->id }}
                    </span>
                @else
                    <span
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                        Nuevo
                    </span>
                @endif
            </div>
        </div>

        <!-- Botón Guardar en la cabecera (patrón moderno compacto) -->
        <button type="button" id="save-requirement-btn"
            class="inline-flex items-center justify-center gap-1.5 rounded-md text-xs font-medium transition-colors bg-zinc-900 text-zinc-50 hover:bg-zinc-800 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200 h-7 px-3 shadow-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
            </svg>
            <span>Guardar Requerimiento</span>
        </button>
    </div>

    <!-- Contenido compacto / Campos de formulario -->
    <div class="p-3.5 sm:p-4">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 sm:gap-4 items-start">

            <!-- Sub-Cliente / Tienda / Sede (Search Input) -->
            <div class="md:col-span-4 relative" x-data="{
                search: '{{ addslashes($record->subClient->name ?? '') }}',
                selectedId: '{{ $record->sub_client_id ?? '' }}',
                items: [],
                isOpen: false,
                loading: false,
                hasSearched: false,
                async fetchItems() {
                    if (!this.search || this.search.trim().length === 0) {
                        this.items = [];
                        this.isOpen = false;
                        return;
                    }
                    this.loading = true;
                    this.isOpen = true;
                    try {
                        const response = await fetch(`{{ route('sub-clients.search') }}?search=${encodeURIComponent(this.search)}&limit=10`);
                        if (response.ok) {
                            const res = await response.json();
                            this.items = res.data || [];
                            this.hasSearched = true;
                        }
                    } catch (error) {
                        console.error('Error buscando tiendas:', error);
                    } finally {
                        this.loading = false;
                    }
                },
                selectItem(item) {
                    this.selectedId = item.id;
                    this.search = item.name;
                    this.isOpen = false;
                },
                clear() {
                    this.selectedId = '';
                    this.search = '';
                    this.items = [];
                    this.isOpen = false;
                    this.hasSearched = false;
                }
            }" @click.outside="isOpen = false">
                <label for="sub_client_search_input"
                    class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                    Tienda / Sede <span class="text-red-500">*</span>
                </label>

                <!-- Hidden Input para enviar el ID en el formulario -->
                <input type="hidden" name="sub_client_id" id="sub_client_id" :value="selectedId" />

                <!-- Input Search -->
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-zinc-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <input type="text" id="sub_client_search_input" x-model="search"
                        @input.debounce.300ms="fetchItems()" @focus="if (items.length > 0) isOpen = true"
                        placeholder="Buscar tienda o sede..." class="shadcn-input !h-9 !text-xs sm:!text-sm !pl-8 !pr-8"
                        autocomplete="off" />

                    <!-- Spinner de carga o botón de limpiar -->
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                        <template x-if="loading">
                            <svg class="animate-spin h-3.5 w-3.5 text-zinc-400" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <template x-if="!loading && (search || selectedId)">
                            <button type="button" @click="clear()"
                                class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors p-0.5 rounded"
                                title="Limpiar selección">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Dropdown de resultados -->
                <div x-show="isOpen" x-cloak
                    class="absolute left-0 top-full mt-1 w-full z-50 overflow-hidden rounded-md border border-zinc-200 bg-white text-zinc-950 shadow-md dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50">
                    <div class="max-h-56 overflow-y-auto p-1 divide-y divide-zinc-100 dark:divide-zinc-900">
                        <!-- Lista de coincidencias -->
                        <template x-for="item in items" :key="item.id">
                            <div @click="selectItem(item)"
                                class="cursor-pointer select-none rounded px-3 py-2 text-xs transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800 flex flex-col gap-0.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100"
                                        x-text="item.name"></span>
                                    <span x-show="item.client?.businessName"
                                        class="text-[10px] text-zinc-400 dark:text-zinc-500 font-normal"
                                        x-text="item.client?.businessName"></span>
                                </div>
                                <span x-show="item.address"
                                    class="text-[11px] text-zinc-500 dark:text-zinc-400 truncate"
                                    x-text="item.address"></span>
                            </div>
                        </template>

                        <!-- Estado sin resultados -->
                        <div x-show="!loading && hasSearched && items.length === 0"
                            class="p-3 text-center text-xs text-zinc-500 dark:text-zinc-400">
                            No se encontraron tiendas ni sedes coincidentes.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nombre de la Actividad (8 columnas de 12) -->
            <div class="md:col-span-8">
                <label for="activity_name" class="block text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1.5">
                    Nombre de la Actividad
                </label>
                <input type="text" id="activity_name" name="activity_name" value="{{ $record->activity_name ?? '' }}"
                    placeholder="Ej. Mantenimiento preventivo en tablero eléctrico principal..."
                    class="shadcn-input !h-9 !text-xs sm:!text-sm" />
            </div>
        </div>
    </div>
</div>
