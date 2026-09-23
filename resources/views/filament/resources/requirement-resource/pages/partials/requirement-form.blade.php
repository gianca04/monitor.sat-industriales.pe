<div class="relative z-30 rounded-xl border border-zinc-200/80 bg-white shadow-xs dark:border-zinc-800/80 dark:bg-zinc-950"
    x-data="{
        isEditMode: @js(isset($record) && $record && $record->id),
        requirementId: @js($record->id ?? null),
        subClientId: '{{ $record->sub_client_id ?? '' }}',
        activityName: '{{ addslashes($record->activity_name ?? '') }}',
        items: [],
        saving: false,
        errorMessage: '',
        successMessage: '',
    
        init() {
            window.addEventListener('requirement-items-updated', (event) => {
                this.items = event.detail || [];
            });
            // Solicitar estado inicial por si ya había ítems cargados
            this.$dispatch('request-requirement-items');
        },
    
        get itemsCount() {
            return this.items.length;
        },
    
        async saveRequirement() {
            this.errorMessage = '';
            this.successMessage = '';
    
            if (!this.subClientId) {
                this.errorMessage = 'Debe seleccionar una tienda o sede (subcliente).';
                return;
            }
    
            this.$dispatch('request-requirement-items');
            await new Promise(r => setTimeout(r, 60));
    
            if (!this.items || this.items.length === 0) {
                this.errorMessage = 'Debe agregar al menos un material a la lista del requerimiento.';
                return;
            }
    
            this.saving = true;
    
            try {
                const csrfToken = '{{ csrf_token() }}' || document.querySelector('meta[name=csrf-token]')?.getAttribute('content');
    
                const payload = {
                    sub_client_id: parseInt(this.subClientId),
                    activity_name: this.activityName || null,
                    items: this.items.map(i => ({
                        item_id: i.id,
                        quantity: parseFloat(i.quantity) || 1
                    }))
                };
    
                if (this.isEditMode) {
                    const res = await fetch('/requirements/' + this.requirementId, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    });
    
                    const data = await res.json();
                    if (!res.ok) {
                        if (data.errors) {
                            const first = Object.values(data.errors)[0];
                            this.errorMessage = Array.isArray(first) ? first[0] : first;
                        } else {
                            this.errorMessage = data.message || 'Error al actualizar el requerimiento.';
                        }
                        return;
                    }
    
                    this.successMessage = 'Requerimiento #' + this.requirementId + ' actualizado correctamente con ' + this.items.length + ' materiales.';
                    setTimeout(() => { this.successMessage = ''; }, 3500);
                } else {
                    const res = await fetch('{{ route('requirements.web.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify(payload)
                    });
    
                    const data = await res.json();
                    if (!res.ok) {
                        if (data.errors) {
                            const first = Object.values(data.errors)[0];
                            this.errorMessage = Array.isArray(first) ? first[0] : first;
                        } else {
                            this.errorMessage = data.message || 'Error al registrar el requerimiento.';
                        }
                        return;
                    }
    
                    const newId = data.data?.id || data.id;
                    this.successMessage = '¡Requerimiento #' + newId + ' creado exitosamente con ' + this.items.length + ' materiales! Redirigiendo...';
    
                    setTimeout(() => {
                        window.location.href = '/dashboard/requirements/' + newId + '/edit';
                    }, 1000);
                }
            } catch (err) {
                console.error('Error al guardar requerimiento:', err);
                this.errorMessage = 'Ocurrió un error inesperado al conectar con el servidor.';
            } finally {
                this.saving = false;
            }
        }
    }">
    <!-- Header compacto integrado: Título + Badge + Botón Guardar -->
    <div
        class="flex items-center justify-between px-4 py-2.5 border-b border-zinc-100 dark:border-zinc-800/80 bg-zinc-50/50 dark:bg-zinc-900/30 rounded-t-xl">
        <div class="flex items-center gap-2.5">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 leading-none">
                    Datos del Requerimiento
                </h3>
            </div>

            <!-- Contador dinámico de materiales en creación -->
            <template x-if="!isEditMode && itemsCount > 0">
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/60 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800/60"
                    x-text="itemsCount + (itemsCount === 1 ? ' material' : ' materiales')">
                </span>
            </template>
        </div>

        <!-- Botón Guardar en la cabecera (patrón moderno compacto) -->
        <button type="button" id="save-requirement-btn" @click="saveRequirement()" :disabled="saving"
            class="inline-flex items-center justify-center gap-1.5 rounded-md text-xs font-medium transition-all bg-zinc-900 text-zinc-50 hover:bg-zinc-800 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200 h-8 px-3 shadow-xs disabled:opacity-50 disabled:cursor-not-allowed">
            <template x-if="saving">
                <svg class="animate-spin h-3.5 w-3.5 text-current" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
            </template>
            <template x-if="!saving">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
            </template>
            <span
                x-text="saving ? 'Guardando...' : (isEditMode ? 'Actualizar Requerimiento' : 'Guardar Requerimiento')"></span>
        </button>
    </div>

    <!-- Feedback Banners de Éxito / Error -->
    <div x-show="errorMessage" x-cloak
        class="mx-3.5 mt-3 p-3 rounded-lg bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-900/60 text-xs text-red-700 dark:text-red-300 flex items-center justify-between gap-2 transition-all">
        <div class="flex items-center gap-2">
            <svg class="h-4 w-4 shrink-0 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z"
                    clip-rule="evenodd" />
            </svg>
            <span x-text="errorMessage"></span>
        </div>
        <button type="button" @click="errorMessage = ''"
            class="text-red-400 hover:text-red-600 font-bold">&times;</button>
    </div>

    <div x-show="successMessage" x-cloak
        class="mx-3.5 mt-3 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/60 text-xs text-emerald-700 dark:text-emerald-300 flex items-center justify-between gap-2 transition-all">
        <div class="flex items-center gap-2">
            <svg class="h-4 w-4 shrink-0 text-emerald-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                    clip-rule="evenodd" />
            </svg>
            <span x-text="successMessage"></span>
        </div>
        <button type="button" @click="successMessage = ''"
            class="text-emerald-400 hover:text-emerald-600 font-bold">&times;</button>
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
                    this.loading = true;
                    this.isOpen = true;
                    try {
                        const trimmed = (this.search || '').trim();
                        const url = trimmed.length > 0 ?
                            `{{ route('sub-clients.search') }}?search=${encodeURIComponent(trimmed)}&per_page=20` :
                            `{{ route('sub-clients.search') }}?per_page=50`;
            
                        const response = await fetch(url);
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
                    subClientId = item.id;
                },
                clear() {
                    this.selectedId = '';
                    this.search = '';
                    this.hasSearched = false;
                    subClientId = '';
                    this.fetchItems();
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
                        @input.debounce.400ms="fetchItems()"
                        @focus="if (items.length === 0 || !search || search.trim() === '') { fetchItems(); } else { isOpen = true; }"
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
                    class="absolute left-0 top-full mt-1 w-full z-50 overflow-hidden rounded-md border border-zinc-200/90 bg-white text-zinc-950 shadow-xl dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-50">
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
                <input type="text" id="activity_name" name="activity_name" x-model="activityName"
                    value="{{ $record->activity_name ?? '' }}" class="shadcn-input !h-9 !text-xs sm:!text-sm" />
            </div>
        </div>
    </div>
</div>
