<div id="category-subcategory-modal-root" x-data="{
    isOpen: false,
    activeTab: 'category',
    categories: [],
    loadingCategories: false,
    loadingSubmit: false,
    errorMessage: '',
    successMessage: '',

    categoryForm: {
        name: '',
        description: ''
    },

    subcategoryForm: {
        category_id: '',
        name: ''
    },

    async openModal(tab = 'category', preselectedCategoryId = null) {
        this.activeTab = tab;
        this.errorMessage = '';
        this.successMessage = '';
        this.isOpen = true;
        this.$dispatch('category-modal-opened');
        await this.loadCategories();
        if (preselectedCategoryId) {
            this.subcategoryForm.category_id = preselectedCategoryId;
        }
    },

    closeModal() {
        this.isOpen = false;
        this.$dispatch('category-modal-closed');
        this.errorMessage = '';
        this.successMessage = '';
        this.categoryForm = { name: '', description: '' };
        this.subcategoryForm = { category_id: '', name: '' };
    },

    async loadCategories() {
        this.loadingCategories = true;
        try {
            const res = await fetch(`{{ route('categories.search') }}?per_page=100`);
            if (res.ok) {
                const json = await res.json();
                this.categories = json.data || [];
                if (this.categories.length > 0 && !this.subcategoryForm.category_id) {
                    this.subcategoryForm.category_id = this.categories[0].id;
                }
            }
        } catch (err) {
            console.error('Error cargando categorías:', err);
        } finally {
            this.loadingCategories = false;
        }
    },

    async submitCategory() {
        this.errorMessage = '';
        this.successMessage = '';
        if (!this.categoryForm.name.trim()) {
            this.errorMessage = 'El nombre de la categoría es requerido.';
            return;
        }

        this.loadingSubmit = true;
        try {
            const res = await fetch(`{{ route('categories.store') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.categoryForm)
            });

            const data = await res.json();
            if (res.ok) {
                const newCategory = data.data;
                this.$dispatch('category-created', newCategory);
                this.successMessage = '¡Categoría ' + newCategory.name + ' creada exitosamente!';
                this.categoryForm = { name: '', description: '' };
                await this.loadCategories();
                this.subcategoryForm.category_id = newCategory.id;
            } else {
                this.errorMessage = data.message || 'Error al guardar la categoría.';
                if (data.errors && data.errors.name) {
                    this.errorMessage = data.errors.name[0];
                }
            }
        } catch (err) {
            this.errorMessage = 'Ocurrió un error en la conexión.';
        } finally {
            this.loadingSubmit = false;
        }
    },

    async submitSubcategory() {
        this.errorMessage = '';
        this.successMessage = '';
        if (!this.subcategoryForm.category_id) {
            this.errorMessage = 'Debe seleccionar una categoría padre.';
            return;
        }
        if (!this.subcategoryForm.name.trim()) {
            this.errorMessage = 'El nombre de la subcategoría es requerido.';
            return;
        }

        this.loadingSubmit = true;
        try {
            const res = await fetch(`{{ route('subcategories.store') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(this.subcategoryForm)
            });

            const data = await res.json();
            if (res.ok) {
                const newSubcategory = data.data;
                this.$dispatch('subcategory-created', newSubcategory);
                this.successMessage = '¡Subcategoría ' + newSubcategory.name + ' creada exitosamente!';
                this.subcategoryForm.name = '';
            } else {
                this.errorMessage = data.message || 'Error al guardar la subcategoría.';
                if (data.errors && data.errors.name) {
                    this.errorMessage = data.errors.name[0];
                }
            }
        } catch (err) {
            this.errorMessage = 'Ocurrió un error en la conexión.';
        } finally {
            this.loadingSubmit = false;
        }
    }
}"
    @open-category-modal.window="openModal('category', $event.detail?.categoryId)"
    @open-subcategory-modal.window="openModal('subcategory', $event.detail?.categoryId)"
    @keydown.escape.window="if(isOpen) closeModal()">
    <!-- Modal Backdrop Container -->
    <div x-show="isOpen" x-cloak
        class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-transparent pointer-events-auto"
        @click.self="closeModal()">
        <!-- Dialog Container (Shadcn style) -->
        <div @click.outside="closeModal()" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="relative w-full max-w-md rounded-xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-950">
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
            <div class="space-y-1 pr-6">
                <h3 class="text-base font-semibold leading-none tracking-tight text-zinc-900 dark:text-zinc-100">
                    Clasificación de Materiales
                </h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    Crea una categoría principal o una subcategoría asociada.
                </p>
            </div>

            <!-- Shadcn Tab Switcher -->
            <div
                class="inline-flex h-9 w-full items-center justify-center rounded-lg bg-zinc-100 p-1 text-xs text-zinc-500 dark:bg-zinc-900 dark:text-zinc-400 mt-4 mb-4">
                <button type="button" @click="activeTab = 'category'; errorMessage = ''; successMessage = '';"
                    :class="activeTab === 'category' ?
                        'bg-white text-zinc-950 shadow-sm dark:bg-zinc-950 dark:text-zinc-50 font-medium' :
                        'hover:text-zinc-900 dark:hover:text-zinc-200'"
                    class="inline-flex flex-1 items-center justify-center rounded-md py-1 transition-all">
                    Nueva Categoría
                </button>
                <button type="button" @click="activeTab = 'subcategory'; errorMessage = ''; successMessage = '';"
                    :class="activeTab === 'subcategory' ?
                        'bg-white text-zinc-950 shadow-sm dark:bg-zinc-950 dark:text-zinc-50 font-medium' :
                        'hover:text-zinc-900 dark:hover:text-zinc-200'"
                    class="inline-flex flex-1 items-center justify-center rounded-md py-1 transition-all">
                    Nueva Subcategoría
                </button>
            </div>

            <!-- Mensajes de feedback -->
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

            <!-- Formulario 1: Categoría -->
            <form x-show="activeTab === 'category'" @submit.prevent="submitCategory()" class="space-y-4">
                <div class="space-y-1.5">
                    <label
                        class="text-xs font-medium text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                        <span>Nombre de la Categoría <span class="text-red-500">*</span></span>
                    </label>
                    <input type="text" x-model="categoryForm.name" class="shadcn-input !h-9 !text-xs" autofocus />
                </div>

                <div class="space-y-1.5">
                    <label
                        class="text-xs font-medium text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                        <span>Descripción</span>
                        <span class="text-[10px] text-zinc-400 font-normal">Opcional</span>
                    </label>
                    <textarea x-model="categoryForm.description" rows="2"
                        placeholder="Descripción breve de los suministros incluidos..."
                        class="shadcn-input !h-16 !text-xs py-2 resize-none"></textarea>
                </div>

                <!-- Footer Acciones -->
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-900">
                    <button type="button" @click="closeModal()"
                        class="inline-flex items-center justify-center rounded-md border border-zinc-200 bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-sm hover:bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="loadingSubmit"
                        class="inline-flex items-center justify-center rounded-md bg-zinc-900 px-4 py-1.5 text-xs font-medium text-zinc-50 shadow hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-50/90 transition-colors disabled:opacity-50">
                        <template x-if="loadingSubmit">
                            <svg class="animate-spin -ml-0.5 mr-1.5 h-3.5 w-3.5 text-current"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <span x-text="loadingSubmit ? 'Guardando...' : 'Crear Categoría'"></span>
                    </button>
                </div>
            </form>

            <!-- Formulario 2: Subcategoría -->
            <form x-show="activeTab === 'subcategory'" @submit.prevent="submitSubcategory()" class="space-y-4">
                <div class="space-y-1.5">
                    <label
                        class="text-xs font-medium text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                        <span>Categoría Padre <span class="text-red-500">*</span></span>
                    </label>
                    <select x-model="subcategoryForm.category_id" class="shadcn-select !h-9 !text-xs"
                        :disabled="loadingCategories">
                        <option value="" disabled>Seleccione categoría...</option>
                        <template x-for="cat in categories" :key="cat.id">
                            <option :value="cat.id" x-text="cat.name"></option>
                        </template>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label
                        class="text-xs font-medium text-zinc-900 dark:text-zinc-100 flex items-center justify-between">
                        <span>Nombre de la Subcategoría <span class="text-red-500">*</span></span>
                    </label>
                    <input type="text" x-model="subcategoryForm.name" class="shadcn-input !h-9 !text-xs" />
                </div>

                <!-- Footer Acciones -->
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-zinc-100 dark:border-zinc-900">
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
                        <span x-text="loadingSubmit ? 'Guardando...' : 'Crear Subcategoría'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
