<div x-data="{
    isOpen: false,
    childModalOpen: false,
    loadingSubmit: false,
    loadingCategories: false,
    loadingSubcategories: false,
    loadingUnits: false,
    errorMessage: '',
    successMessage: '',

    categories: [],
    subcategories: [],
    units: [],

    selectedCategoryId: '',

    form: {
        name: '',
        sku: '',
        subcategory_id: '',
        unit_id: '',
        autoAdd: true
    },

    photoPreview: null,
    photoFile: null,

    async openModal(prefill = {}) {
        this.resetForm();
        this.isOpen = true;
        await Promise.all([
            this.loadCategories(),
            this.loadUnits()
        ]);

        if (prefill && prefill.name) {
            this.form.name = prefill.name;
        }
        if (prefill && prefill.categoryId) {
            this.selectedCategoryId = prefill.categoryId;
            await this.loadSubcategories(prefill.categoryId);
        }
        if (prefill && prefill.subcategoryId) {
            this.form.subcategory_id = prefill.subcategoryId;
        }
    },

    closeModal() {
        this.isOpen = false;
        this.resetForm();
    },

    resetForm() {
        this.errorMessage = '';
        this.successMessage = '';
        this.selectedCategoryId = '';
        this.subcategories = [];
        this.form = {
            name: '',
            sku: '',
            subcategory_id: '',
            unit_id: this.units.length > 0 ? this.units[0].id : '',
            autoAdd: true
        };
        this.photoPreview = null;
        this.photoFile = null;
        this.$dispatch('reset-photo');
        if (this.$refs.photoInput) {
            this.$refs.photoInput.value = '';
        }
    },

    async loadCategories() {
        if (this.categories.length > 0) return;
        this.loadingCategories = true;
        try {
            const res = await fetch(`{{ route('categories.search') }}?per_page=100`);
            if (res.ok) {
                const json = await res.json();
                this.categories = json.data || [];
            }
        } catch (err) {
            console.error('Error al cargar categorías:', err);
        } finally {
            this.loadingCategories = false;
        }
    },

    async loadSubcategories(categoryId) {
        if (!categoryId) {
            this.subcategories = [];
            this.form.subcategory_id = '';
            return;
        }
        this.loadingSubcategories = true;
        try {
            const res = await fetch(`{{ route('subcategories.search') }}?category_id=${categoryId}&per_page=100`);
            if (res.ok) {
                const json = await res.json();
                this.subcategories = json.data || [];
                if (!this.subcategories.some(s => s.id == this.form.subcategory_id)) {
                    this.form.subcategory_id = this.subcategories.length > 0 ? this.subcategories[0].id : '';
                }
            }
        } catch (err) {
            console.error('Error al cargar subcategorías:', err);
        } finally {
            this.loadingSubcategories = false;
        }
    },

    async onCategoryChange() {
        this.form.subcategory_id = '';
        await this.loadSubcategories(this.selectedCategoryId);
    },

    async loadUnits() {
        if (this.units.length > 0) return;
        this.loadingUnits = true;
        try {
            const res = await fetch(`{{ route('units.search') }}?per_page=100`);
            if (res.ok) {
                const json = await res.json();
                this.units = json.data || [];
                if (this.units.length > 0 && !this.form.unit_id) {
                    this.form.unit_id = this.units[0].id;
                }
            }
        } catch (err) {
            console.error('Error al cargar unidades:', err);
        } finally {
            this.loadingUnits = false;
        }
    },

    onCategoryCreated(newCat) {
        if (!newCat) return;
        const exists = this.categories.some(c => c.id == newCat.id);
        if (!exists) {
            this.categories.unshift(newCat);
        }
        this.selectedCategoryId = newCat.id;
        this.loadSubcategories(newCat.id);
    },

    onSubcategoryCreated(newSub) {
        if (!newSub) return;
        if (newSub.category_id) {
            this.selectedCategoryId = newSub.category_id;
            this.loadSubcategories(newSub.category_id).then(() => {
                this.form.subcategory_id = newSub.id;
            });
        }
    },

    handlePhotoChange(event) {
        const file = event.target.files[0];
        if (file) {
            this.photoFile = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.photoPreview = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    },

    removePhoto() {
        this.photoFile = null;
        this.photoPreview = null;
        if (this.$refs.photoInput) {
            this.$refs.photoInput.value = '';
        }
    },

    async submitItem() {
        this.errorMessage = '';
        this.successMessage = '';

        if (!this.form.name.trim()) {
            this.errorMessage = 'El nombre del ítem es requerido.';
            return;
        }
        if (!this.form.subcategory_id) {
            this.errorMessage = 'Debe seleccionar una categoría y subcategoría.';
            return;
        }

        this.loadingSubmit = true;
        try {
            const formData = new FormData();
            formData.append('name', this.form.name.trim());
            if (this.form.sku.trim()) {
                formData.append('sku', this.form.sku.trim());
            }
            formData.append('subcategory_id', this.form.subcategory_id);
            if (this.form.unit_id) {
                formData.append('unit_id', this.form.unit_id);
            }
            if (this.photoFile) {
                formData.append('photo', this.photoFile);
            }

            const res = await fetch(`{{ route('items.store') }}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });

            const data = await res.json();
            if (res.ok) {
                const newItem = data.data;
                this.$dispatch('item-created', newItem);
                if (this.form.autoAdd) {
                    this.$dispatch('item-selected', newItem);
                }
                this.successMessage = '¡Ítem creado exitosamente!';
                setTimeout(() => {
                    this.closeModal();
                }, 700);
            } else {
                this.errorMessage = data.message || 'Error al crear el ítem.';
                if (data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey && data.errors[firstKey][0]) {
                        this.errorMessage = data.errors[firstKey][0];
                    }
                }
            }
        } catch (err) {
            this.errorMessage = 'Ocurrió un error en la conexión.';
        } finally {
            this.loadingSubmit = false;
        }
    }
}" @open-item-modal.window="openModal($event.detail)"
    @category-created.window="onCategoryCreated($event.detail)"
    @subcategory-created.window="onSubcategoryCreated($event.detail)"
    @category-modal-opened.window="childModalOpen = true"
    @category-modal-closed.window="setTimeout(() => { childModalOpen = false }, 150)"
    @keydown.escape.window="if(isOpen && !childModalOpen) closeModal()">
    <!-- Modal Backdrop Container -->
    <div x-show="isOpen" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-transparent pointer-events-auto"
        @click.self="if(!childModalOpen && !$event.target.closest('#category-subcategory-modal-root')) closeModal()">
        <!-- Dialog Container (Shadcn style) -->
        <div @click.outside="if(!childModalOpen && !$event.target.closest('#category-subcategory-modal-root')) closeModal()"
            x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-950 max-h-[90vh] overflow-y-auto">
            <!-- Close Button -->
            <button type="button" @click="closeModal()"
                class="absolute right-4 top-4 rounded-sm p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors"
                title="Cerrar modal">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Dialog Header -->
            <div class="space-y-1 pr-6 mb-5">
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-semibold leading-none tracking-tight text-zinc-900 dark:text-zinc-100">
                        Nuevo Ítem de Catálogo
                    </h3>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Registra un nuevo material o suministro en el catálogo del sistema.
                </p>
            </div>

            <!-- Feedback Messages -->
            <template x-if="errorMessage">
                <div
                    class="mb-4 rounded-md border border-red-200 bg-red-50/50 p-2.5 text-xs text-red-600 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-400 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-red-500" viewBox="0 0 20 20"
                        fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                    <span x-text="errorMessage"></span>
                </div>
            </template>

            <template x-if="successMessage">
                <div
                    class="mb-4 rounded-md border border-emerald-200 bg-emerald-50/50 p-2.5 text-xs text-emerald-600 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-400 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-emerald-500"
                        viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <span x-text="successMessage"></span>
                </div>
            </template>

            <!-- Form -->
            <form @submit.prevent="submitItem()" class="space-y-4">
                <!-- Nombre del Ítem -->
                <div class="space-y-1.5">
                    <label
                        class="text-xs font-medium text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                        <span>Nombre del Ítem <span class="text-red-500">*</span></span>
                    </label>
                    <input type="text" x-model="form.name" class="shadcn-input !h-9 !text-xs" autofocus />
                </div>

                <!-- Grid: Categoría y Subcategoría -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <!-- Categoría -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <!-- Botón para abrir modal de Categoría -->
                            <button type="button" @click="$dispatch('open-category-modal')"
                                class="inline-flex items-center gap-1 text-[11px] font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors"
                                title="Crear nueva categoría">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Nueva</span>
                            </button>
                        </div>
                        <select x-model="selectedCategoryId" @change="onCategoryChange()"
                            class="shadcn-select !h-9 !text-xs" :disabled="loadingCategories">
                            <option value="" disabled>Seleccione categoría...</option>
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.name"></option>
                            </template>
                        </select>
                    </div>

                    <!-- Subcategoría -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                Subcategoría <span class="text-red-500">*</span>
                            </label>
                            <!-- Botón para abrir modal de Subcategoría -->
                            <button type="button"
                                @click="$dispatch('open-subcategory-modal', { categoryId: selectedCategoryId })"
                                class="inline-flex items-center gap-1 text-[11px] font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors"
                                title="Crear nueva subcategoría">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Nueva</span>
                            </button>
                        </div>
                        <select x-model="form.subcategory_id" class="shadcn-select !h-9 !text-xs"
                            :disabled="!selectedCategoryId || loadingSubcategories">
                            <option value="" disabled
                                x-text="!selectedCategoryId ? 'Seleccione categoría primero...' : 'Seleccione subcategoría...'">
                            </option>
                            <template x-for="sub in subcategories" :key="sub.id">
                                <option :value="sub.id" x-text="sub.name"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <!-- Grid: Unidad de Medida y SKU -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <!-- Unidad de Medida -->
                    <div class="space-y-1.5">
                        <label
                            class="text-xs font-medium text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                            <span>Unidad de Medida</span>
                        </label>
                        <select x-model="form.unit_id" class="shadcn-select !h-9 !text-xs" :disabled="loadingUnits">
                            <option value="">Sin unidad especificada</option>
                            <template x-for="u in units" :key="u.id">
                                <option :value="u.id"
                                    x-text="u.name + (u.symbol ? ' (' + u.symbol + ')' : '')"></option>
                            </template>
                        </select>
                    </div>

                    <!-- SKU personalizado u opcional -->
                    <div class="space-y-1.5">
                        <label
                            class="text-xs font-medium text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                            <span>Código SKU</span>
                            <span class="text-[10px] text-zinc-400 font-normal">Autogenerado si está vacío</span>
                        </label>
                        <input type="text" x-model="form.sku" class="shadcn-input !h-9 !text-xs" />
                    </div>
                </div>

                <!-- Foto del Ítem (Componente Shadcn con Soporte Portapapeles y Drag & Drop) -->
                <div @photo-changed="photoFile = $event.detail.file; photoPreview = $event.detail.preview"
                    @photo-removed="photoFile = null; photoPreview = null">
                    <x-image-uploader label="Foto del Material" name="photo" />
                </div>

                <!-- Toggle: Agregar directamente al requerimiento -->
                <div class="pt-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" x-model="form.autoAdd"
                            class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:checked:bg-zinc-50 dark:focus:ring-zinc-400 h-4 w-4" />
                        <span class="text-xs text-zinc-700 dark:text-zinc-300">
                            Agregar automáticamente este ítem a la lista de requerimiento al crearlo
                        </span>
                    </label>
                </div>

                <!-- Footer Acciones -->
                <div class="flex items-center justify-end gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-900">
                    <button type="button" @click="closeModal()"
                        class="inline-flex items-center justify-center rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-sm hover:bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="loadingSubmit"
                        class="inline-flex items-center justify-center rounded-md bg-zinc-900 px-4 py-1.5 text-xs font-medium text-zinc-50 shadow hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-50/90 transition-colors disabled:opacity-50">
                        <template x-if="loadingSubmit">
                            <svg class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-current"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <span x-text="loadingSubmit ? 'Guardando...' : 'Crear Ítem'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
