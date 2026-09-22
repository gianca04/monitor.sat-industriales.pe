<div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950">
    <!-- Card Header (Shadcn style) -->
    <div class="flex flex-col space-y-1.5 p-6 border-b border-zinc-100 dark:border-zinc-900">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div>
                    <h3 class="font-semibold leading-none tracking-tight text-zinc-900 dark:text-zinc-100 text-base">
                        Datos del Requerimiento
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                        Información principal y destino de la solicitud de materiales.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Content / Form Fields (Shadcn style) -->
    <div class="p-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">

            <!-- Sub-Cliente / Tienda / Sede (Minimal Search Input) -->
            <div class="space-y-2 relative" x-data="{
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
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                    <span>Tienda <span class="text-red-500">*</span></span>
                    <span class="text-[11px] text-zinc-400">Destino del material</span>
                </label>

                <!-- Hidden Input para enviar el ID en el formulario -->
                <input type="hidden" name="sub_client_id" id="sub_client_id" :value="selectedId" />

                <!-- Input Search -->
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                    <input type="text" id="sub_client_search_input" x-model="search"
                        @input.debounce.300ms="fetchItems()" @focus="if (items.length > 0) isOpen = true"
                        placeholder="Buscar tienda o sede..." class="shadcn-input !pl-9 !pr-8" autocomplete="off" />

                    <!-- Spinner de carga o botón de limpiar -->
                    <div class="absolute inset-y-0 right-0 flex items-center pr-2.5">
                        <template x-if="loading">
                            <svg class="animate-spin h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg"
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
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
                                class="cursor-pointer select-none rounded px-3 py-2 text-sm transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800 flex flex-col gap-0.5">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100"
                                        x-text="item.name"></span>
                                    <span x-show="item.client?.businessName"
                                        class="text-[10px] text-zinc-400 dark:text-zinc-500 font-normal"
                                        x-text="item.client?.businessName"></span>
                                </div>
                                <span x-show="item.address" class="text-xs text-zinc-500 dark:text-zinc-400 truncate"
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

            <!-- Nombre de la Actividad -->
            <div class="space-y-2 md:col-span-1 lg:col-span-2">
                <label for="activity_name"
                    class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                    <span>Nombre de la Actividad</span>
                    <span class="text-[11px] text-zinc-400">Descripción breve del trabajo</span>
                </label>
                <input type="text" id="activity_name" name="activity_name"
                    placeholder="Ej. Mantenimiento preventivo en tablero eléctrico principal..." class="shadcn-input" />
            </div>
        </div>
    </div>

    <!-- Card Footer (Shadcn style) -->
    <div
        class="flex items-center justify-between px-6 py-4 bg-zinc-50/50 border-t border-zinc-100 dark:bg-zinc-900/30 dark:border-zinc-900 rounded-b-xl">
        <div class="flex items-center gap-2">
            <button type="button"
                class="inline-flex items-center justify-center rounded-md text-xs font-medium transition-colors bg-zinc-900 text-zinc-50 hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-50/90 h-8 px-4 shadow">
                Guardar Requerimiento
            </button>
        </div>
    </div>
</div>
