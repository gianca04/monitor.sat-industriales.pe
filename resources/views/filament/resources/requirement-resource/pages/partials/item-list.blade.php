@php
    $currentRecord = $record ?? ($this->record ?? null);
    $isEditMode = isset($currentRecord) && $currentRecord && $currentRecord->id;
    $requirementId = $isEditMode ? $currentRecord->id : null;
    $csrfToken = csrf_token();

    $initialItems = [];
    if ($isEditMode && $currentRecord->relationLoaded('requirementLists')) {
        $initialItems = $currentRecord->requirementLists
            ->map(function ($rl) {
                return [
                    'list_id' => $rl->id,
                    'id' => $rl->item_id,
                    'name' => $rl->item->name ?? 'Material',
                    'sku' => $rl->item->sku ?? '',
                    'unit' => $rl->item->unit->symbol ?? ($rl->item->unit->name ?? 'UND'),
                    'quantity' => (float) $rl->quantity,
                    'category_id' => $rl->item->subcategory->category_id ?? '',
                    'subcategory_id' => $rl->item->subcategory_id ?? '',
                    'photo' => $rl->item?->photo_url ?? null,
                ];
            })
            ->values()
            ->all();
    }
@endphp

<div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950 flex flex-col h-full"
    x-data="{
        isEditMode: @js($isEditMode),
        requirementId: @js($requirementId),
        csrfToken: '{{ $csrfToken }}',
    
        // Todos los ítems se manipulan localmente en memoria sin peticiones al interactuar
        localItems: @js($initialItems),
    
        // Paginación y Filtros locales
        page: 1,
        perPage: 10,
        total: 0,
        lastPage: 1,
        from: 0,
        to: 0,
    
        search: '',
        selectedCategoryId: '',
        selectedSubcategoryId: '',
        categories: [],
        subcategories: [],
    
        loading: false,
        activeMenuIndex: null,
        previewImage: null,
        selectedItems: [],
    
        getCsrfToken() {
            return this.csrfToken || document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || '';
        },
    
        init() {
            this.loadCategories();
            this.notifyChange();
        },
    
        async loadCategories() {
            try {
                if (!window._cachedCategoriesPromise) {
                    window._cachedCategoriesPromise = fetch('{{ route('categories.search') }}?per_page=100')
                        .then(r => r.ok ? r.json() : { data: [] })
                        .catch(err => { console.error('Error cargando categorías en lista:', err); return { data: [] }; });
                }
                const json = await window._cachedCategoriesPromise;
                this.categories = json.data || [];
            } catch (err) {
                console.error('Error cargando categorías en lista:', err);
            }
        },
    
        async onCategoryChange() {
            this.selectedSubcategoryId = '';
            this.subcategories = [];
            if (this.selectedCategoryId) {
                try {
                    const res = await fetch('{{ route('subcategories.search') }}?category_id=' + this.selectedCategoryId + '&per_page=100');
                    if (res.ok) {
                        const json = await res.json();
                        this.subcategories = json.data || [];
                    }
                } catch (err) {
                    console.error('Error cargando subcategorías:', err);
                }
            }
            this.page = 1;
        },
    
        refreshItems(page = 1) {
            this.page = page;
        },
    
        get visibleItems() {
            let filtered = this.localItems;
            if (this.search && this.search.trim().length > 0) {
                const term = this.search.trim().toLowerCase();
                filtered = filtered.filter(i =>
                    i.name.toLowerCase().includes(term) ||
                    (i.sku && i.sku.toLowerCase().includes(term))
                );
            }
            if (this.selectedCategoryId) {
                filtered = filtered.filter(i => String(i.category_id) === String(this.selectedCategoryId));
            }
            if (this.selectedSubcategoryId) {
                filtered = filtered.filter(i => String(i.subcategory_id) === String(this.selectedSubcategoryId));
            }
    
            this.total = filtered.length;
            this.lastPage = Math.max(1, Math.ceil(this.total / this.perPage));
            if (this.page > this.lastPage) this.page = this.lastPage;
    
            const start = (this.page - 1) * this.perPage;
            this.from = this.total > 0 ? start + 1 : 0;
            this.to = Math.min(start + this.perPage, this.total);
    
            return filtered.slice(start, start + this.perPage);
        },
    
        addItem(item) {
            if (!item || !item.id) return;
    
            const existing = this.localItems.find(i => i.id === item.id);
            if (existing) {
                existing.quantity = Math.round(((parseFloat(existing.quantity) || 0) + 1) * 100) / 100;
            } else {
                this.localItems.push({
                    id: item.id,
                    name: item.name,
                    sku: item.sku || '',
                    unit: item.unit ? (item.unit.symbol || item.unit.name) : 'UND',
                    quantity: 1,
                    category_id: item.subcategory?.category_id || '',
                    subcategory_id: item.subcategory_id || '',
                    photo: item.photo || item.photo_url || null,
                });
            }
            this.notifyChange();
        },
    
        updateQuantity(item, value) {
            const num = parseFloat(value);
            const qty = isNaN(num) || num <= 0 ? 1 : Math.round(num * 100) / 100;
            item.quantity = qty;
            this.notifyChange();
        },
    
        increment(item) {
            const current = parseFloat(item.quantity) || 0;
            this.updateQuantity(item, current + 1);
        },
    
        decrement(item) {
            const current = parseFloat(item.quantity) || 1;
            if (current > 1) {
                this.updateQuantity(item, current - 1);
            }
        },
    
        removeItem(item) {
            this.activeMenuIndex = null;
            this.localItems = this.localItems.filter(i => i.id !== item.id);
            if (this.visibleItems.length === 0 && this.page > 1) {
                this.page--;
            }
            this.notifyChange();
        },
    

    
        clearAll() {
            if (this.localItems.length === 0) return;
    
            this.$dispatch('open-confirm-modal', {
                title: '¿Vaciar todos los ítems agregados?',
                description: 'Se eliminarán todos los ' + this.localItems.length + ' materiales de esta lista. Esta acción se confirmará permanentemente al guardar/actualizar el requerimiento.',
                confirmText: 'Sí, vaciar lista',
                cancelText: 'Cancelar',
                variant: 'danger',
                action: 'clear-all-items'
            });
        },
    
        clearAllConfirmed() {
            this.localItems = [];
            this.page = 1;
            this.activeMenuIndex = null;
            this.notifyChange();
        },
    
        notifyChange() {
            this.$dispatch('requirement-items-updated', this.localItems);
        },
    
        totalQuantity() {
            const sum = this.localItems.reduce((acc, curr) => acc + (parseFloat(curr.quantity) || 0), 0);
            return Math.round(sum * 100) / 100;
        }
    }" @item-selected.window="addItem($event.detail)" @click.outside="activeMenuIndex = null"
    @keydown.escape.window="activeMenuIndex = null"
    @confirm-action-confirmed.window="if ($event.detail?.action === 'clear-all-items') clearAllConfirmed()"
    @request-requirement-items.window="notifyChange()">

    <!-- Card Header (Shadcn style) -->
    <div class="flex items-center justify-between p-4 border-b border-zinc-100 dark:border-zinc-900">
        <div class="flex items-center gap-2">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 leading-none">
                    Ítems del Requerimiento
                </h3>
                <p class="text-[11px] text-zinc-400 mt-1">
                    Materiales y cantidades solicitadas con paginación
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <!-- Botón Exportar Selección -->
            <template x-if="selectedItems.length > 0">
                <button type="button" @click="$wire.exportSelectedItems(selectedItems)"
                    class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 dark:hover:text-emerald-300 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    <span x-text="'Exportar (' + selectedItems.length + ')'"></span>
                </button>
            </template>

            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400"
                x-text="total + (total === 1 ? ' material en lista' : ' materiales en lista')">
            </span>

            <template x-if="total > 0">
                <button type="button" @click="clearAll()"
                    class="text-[11px] font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                    Vaciar lista
                </button>
            </template>
        </div>
    </div>

    <!-- Barra de Filtros y Búsqueda en la Lista de Ítems -->
    <div class="p-3 border-b border-zinc-100 dark:border-zinc-900 bg-zinc-50/40 dark:bg-zinc-900/20">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2 items-center">
            <!-- Buscador por texto -->
            <div class="sm:col-span-6 relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-zinc-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" x-model="search" @input.debounce.400ms="refreshItems(1)"
                    placeholder="Filtrar por nombre o SKU..." class="shadcn-input !h-8 !text-xs !pl-8 !pr-7" />
                <div class="absolute inset-y-0 right-0 flex items-center pr-2" x-show="search">
                    <button type="button" @click="search = ''; refreshItems(1)"
                        class="text-zinc-400 hover:text-zinc-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Filtro Categoría -->
            <div class="sm:col-span-3">
                <select x-model="selectedCategoryId" @change="onCategoryChange()" class="shadcn-select-sm">
                    <option value="">Todas las Categorías</option>
                    <template x-for="cat in categories" :key="cat.id">
                        <option :value="cat.id" x-text="cat.name"></option>
                    </template>
                </select>
            </div>

            <!-- Filtro Subcategoría -->
            <div class="sm:col-span-3">
                <select x-model="selectedSubcategoryId" @change="refreshItems(1)" :disabled="!selectedCategoryId"
                    class="shadcn-select-sm">
                    <option value="" x-text="!selectedCategoryId ? 'Subcategoría...' : 'Todas las Subcategorías'">
                    </option>
                    <template x-for="sub in subcategories" :key="sub.id">
                        <option :value="sub.id" x-text="sub.name"></option>
                    </template>
                </select>
            </div>
        </div>
    </div>

    <!-- Table Container con Scroll Suave -->
    <div class="flex-1 overflow-x-auto min-h-[360px] relative">
        <!-- Spinner Loader de tabla -->
        <div x-show="loading"
            class="absolute inset-0 bg-white/70 dark:bg-zinc-950/70 z-20 flex items-center justify-center">
            <svg class="animate-spin h-6 w-6 text-zinc-600 dark:text-zinc-400" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        </div>

        <!-- Empty State -->
        <template x-if="!loading && visibleItems.length === 0">
            <div class="flex flex-col items-center justify-center h-full py-16 px-4 text-center">
                <div
                    class="h-10 w-10 rounded-full bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-zinc-400 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    No se encontraron materiales en la lista
                </h4>
                <p class="text-xs text-zinc-400 mt-1 max-w-sm">
                    Selecciona ítems del catálogo lateral para agregarlos a esta solicitud.
                </p>
            </div>
        </template>

        <!-- Table with Items -->
        <template x-if="visibleItems.length > 0">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="border-b border-zinc-100 dark:border-zinc-900 bg-zinc-50/50 dark:bg-zinc-900/20 text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        <th class="py-2.5 px-3 text-center w-10">
                            <input type="checkbox" 
                                @change="$event.target.checked ? selectedItems = visibleItems.filter(i => i.list_id).map(i => i.list_id) : selectedItems = []"
                                :checked="visibleItems.length > 0 && selectedItems.length > 0 && selectedItems.length === visibleItems.filter(i => i.list_id).length"
                                class="rounded border-zinc-300 text-zinc-900 shadow-sm focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:checked:bg-zinc-100 dark:focus:ring-zinc-100 dark:focus:ring-offset-zinc-950 transition-colors" 
                            />
                        </th>
                        <th class="py-2.5 px-3 text-center w-16">Imagen</th>
                        <th class="py-2.5 px-4">Material</th>
                        <th class="py-2.5 px-3 text-center w-28">Unidad</th>
                        <th class="py-2.5 px-3 text-center w-36">Cantidad</th>
                        <th class="py-2.5 px-4 text-right w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-900 text-xs">
                    <template x-for="(item, index) in visibleItems" :key="(item.list_id || item.id) + '-' + index">
                        <tr class="group hover:bg-zinc-50/60 dark:hover:bg-zinc-900/40 transition-colors">
                            <!-- Col 0: Checkbox -->
                            <td class="py-2.5 px-3 text-center w-10">
                                <template x-if="item.list_id">
                                    <input type="checkbox" 
                                        :value="item.list_id"
                                        x-model="selectedItems"
                                        class="rounded border-zinc-300 text-zinc-900 shadow-sm focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:checked:bg-zinc-100 dark:focus:ring-zinc-100 dark:focus:ring-offset-zinc-950 transition-colors" 
                                    />
                                </template>
                            </td>
                            
                            <!-- Col 1: Imagen del Ítem -->
                            <td class="py-2.5 px-3 text-center w-16">
                                <template x-if="item.photo">
                                    <div class="inline-flex items-center justify-center">
                                        <button type="button"
                                            @click.stop="previewImage = { url: item.photo, name: item.name, sku: item.sku }"
                                            class="relative group/img h-9 w-9 rounded-lg overflow-hidden border border-zinc-200/80 dark:border-zinc-800 bg-zinc-100 dark:bg-zinc-900 hover:ring-2 hover:ring-zinc-400 dark:hover:ring-zinc-600 transition-all cursor-zoom-in shadow-2xs"
                                            title="Click para ampliar imagen">
                                            <img :src="item.photo" :alt="item.name"
                                                class="h-full w-full object-cover" loading="lazy" />
                                            <div
                                                class="absolute inset-0 bg-black/25 opacity-0 group-hover/img:opacity-100 transition-opacity flex items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                    class="h-3.5 w-3.5 text-white drop-shadow-xs" fill="none"
                                                    viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                                                </svg>
                                            </div>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="!item.photo">
                                    <div class="h-9 w-9 rounded-lg bg-zinc-100/70 dark:bg-zinc-900/40 border border-dashed border-zinc-200 dark:border-zinc-800/80 flex items-center justify-center text-zinc-300 dark:text-zinc-600 mx-auto"
                                        title="Sin imagen disponible">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                </template>
                            </td>

                            <!-- Col 2: Material Name & SKU -->
                            <td class="py-3 px-4">
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100 leading-snug"
                                        x-text="item.name"></span>
                                    <span class="text-[10px] text-zinc-400 mt-0.5"
                                        x-text="item.sku ? 'SKU: ' + item.sku : 'Sin SKU'"></span>
                                </div>
                            </td>

                            <!-- Col 2: Unidad -->
                            <td class="py-3 px-3 text-center">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                    x-text="item.unit || 'UND'"></span>
                            </td>

                            <!-- Col 3: Stepper de Cantidad -->
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center justify-center">
                                    <button type="button" @click="decrement(item)"
                                        class="h-8 w-7 inline-flex items-center justify-center rounded-l-md border border-r-0 border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors select-none text-xs"
                                        title="Reducir">
                                        -
                                    </button>
                                    <input type="number" step="any" min="0.01" :value="item.quantity"
                                        @input.debounce.400ms="updateQuantity(item, $event.target.value)"
                                        class="h-8 w-16 text-center text-xs font-medium border-y border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-400 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                    <button type="button" @click="increment(item)"
                                        class="h-8 w-7 inline-flex items-center justify-center rounded-r-md border border-l-0 border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors select-none text-xs"
                                        title="Aumentar">
                                        +
                                    </button>
                                </div>
                            </td>

                            <!-- Col 5: Opciones Dropdown (Ahora solo Eliminar) -->
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex justify-end">
                                    <button type="button" @click="removeItem(item)"
                                        class="p-1.5 rounded-md text-zinc-400 hover:text-red-600 dark:text-zinc-500 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                                        title="Eliminar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>
    </div>

    <!-- Paginación y Resumen Inferior (Estilo Shadcn) -->
    <div
        class="p-3 border-t border-zinc-100 dark:border-zinc-900 bg-zinc-50/40 dark:bg-zinc-900/20 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <!-- Indicador de filas mostradas -->
        <div class="text-zinc-500 dark:text-zinc-400">
            <template x-if="total > 0">
                <span>
                    Mostrando <strong class="text-zinc-800 dark:text-zinc-200" x-text="from"></strong> a
                    <strong class="text-zinc-800 dark:text-zinc-200" x-text="to"></strong> de
                    <strong class="text-zinc-800 dark:text-zinc-200" x-text="total"></strong> materiales
                </span>
            </template>
            <template x-if="total === 0">
                <span>0 materiales</span>
            </template>
        </div>

        <!-- Controles de Paginación -->
        <div class="flex items-center gap-2">
            <!-- Selector por página -->
            <select x-model="perPage" @change="refreshItems(1)"
                class="shadcn-select-sm !w-auto !h-7 !py-0.5 !pl-2 !pr-6 !text-[11px]">
                <option value="5">5 / pág</option>
                <option value="10">10 / pág</option>
                <option value="25">25 / pág</option>
                <option value="50">50 / pág</option>
            </select>

            <!-- Botón Anterior -->
            <button type="button" @click="refreshItems(page - 1)" :disabled="page <= 1"
                class="inline-flex items-center justify-center h-7 px-2.5 rounded border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-[11px] font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/80 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                Anterior
            </button>

            <!-- Indicador Página actual -->
            <span class="text-[11px] text-zinc-500 px-1" x-text="page + ' / ' + lastPage"></span>

            <!-- Botón Siguiente -->
            <button type="button" @click="refreshItems(page + 1)" :disabled="page >= lastPage"
                class="inline-flex items-center justify-center h-7 px-2.5 rounded border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-[11px] font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-800/80 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                Siguiente
            </button>
        </div>
    </div>

    <!-- Modal Preview de Imagen del Ítem -->
    <template x-teleport="body">
        <div x-show="previewImage" x-cloak
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
            x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            @click.self="previewImage = null" @keydown.escape.window="previewImage = null">

            <div class="relative max-w-sm sm:max-w-md w-full bg-white dark:bg-zinc-950 rounded-xl border border-zinc-200 dark:border-zinc-800 shadow-2xl overflow-hidden"
                x-show="previewImage" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95">

                <!-- Header del Modal Preview -->
                <div
                    class="flex items-center justify-between px-4 py-3 border-b border-zinc-100 dark:border-zinc-900 bg-zinc-50/50 dark:bg-zinc-900/30">
                    <div class="truncate pr-3">
                        <h4 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100 truncate"
                            x-text="previewImage?.name"></h4>
                        <p class="text-[10px] text-zinc-400 mt-0.5"
                            x-text="previewImage?.sku ? 'SKU: ' + previewImage.sku : 'Sin SKU'"></p>
                    </div>
                    <button type="button" @click="previewImage = null"
                        class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 p-1 rounded-md transition-colors"
                        title="Cerrar">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Contenedor de la Imagen -->
                <div
                    class="p-3 bg-zinc-100/50 dark:bg-zinc-900/50 flex items-center justify-center min-h-[220px] max-h-[75vh]">
                    <img :src="previewImage?.url" :alt="previewImage?.name"
                        class="max-h-[70vh] w-auto max-w-full rounded-lg object-contain shadow-sm" />
                </div>
            </div>
        </div>
    </template>
</div>
