<div
    id="confirm-modal-root"
    x-data="{
        isOpen: false,
        title: '¿Estás seguro?',
        description: 'Esta acción no se puede deshacer.',
        confirmText: 'Confirmar',
        cancelText: 'Cancelar',
        variant: 'danger',
        action: '',
        payload: null,

        openModal(params = {}) {
            this.title = params.title || '¿Estás seguro?';
            this.description = params.description || 'Esta acción no se puede deshacer.';
            this.confirmText = params.confirmText || 'Confirmar';
            this.cancelText = params.cancelText || 'Cancelar';
            this.variant = params.variant || 'danger';
            this.action = params.action || '';
            this.payload = params.payload || null;
            this.isOpen = true;
        },

        closeModal() {
            this.isOpen = false;
        },

        confirm() {
            this.$dispatch('confirm-action-confirmed', {
                action: this.action,
                payload: this.payload
            });
            this.closeModal();
        }
    }"
    @open-confirm-modal.window="openModal($event.detail)"
    @keydown.escape.window="if (isOpen) closeModal()"
>
    <!-- Modal Backdrop Container con efecto blur y transición suave -->
    <div
        x-show="isOpen"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[70] flex items-center justify-center p-4 bg-zinc-950/50 backdrop-blur-xs pointer-events-auto"
        @click.self="closeModal()"
    >
        <!-- Diálogo Shadcn AlertDialog -->
        <div
            @click.outside="closeModal()"
            x-show="isOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2"
            class="relative w-full max-w-md rounded-xl border border-zinc-200 bg-white p-5 sm:p-6 shadow-2xl dark:border-zinc-800 dark:bg-zinc-950 text-left"
        >
            <!-- Botón Cerrar (X superior derecha) -->
            <button
                type="button"
                @click="closeModal()"
                class="absolute right-4 top-4 rounded-sm p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 transition-colors"
                title="Cerrar modal"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Cabecera e icono semántico -->
            <div class="flex items-start gap-3.5 pr-6">
                <!-- Avatar / Icono según variante -->
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400 border border-red-100 dark:border-red-900/30': variant === 'danger',
                        'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-100 dark:border-amber-900/30': variant === 'warning',
                        'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700': variant === 'info'
                    }"
                >
                    <!-- Peligro / Destructivo (Papelera) -->
                    <template x-if="variant === 'danger'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </template>
                    <!-- Advertencia (Triángulo) -->
                    <template x-if="variant === 'warning'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </template>
                    <!-- Info (Círculo i) -->
                    <template x-if="variant === 'info'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </div>

                <!-- Título y descripción con excelente legibilidad -->
                <div class="flex-1 space-y-1 pt-0.5">
                    <h3 class="text-sm sm:text-base font-semibold text-zinc-900 dark:text-zinc-100 leading-snug" x-text="title">
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed" x-text="description">
                    </p>
                </div>
            </div>

            <!-- Footer de botones de acción -->
            <div class="mt-6 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2">
                <button
                    type="button"
                    @click="closeModal()"
                    class="inline-flex h-8 sm:h-9 items-center justify-center rounded-md border border-zinc-200 bg-white px-3.5 text-xs font-medium text-zinc-700 shadow-2xs hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-900 transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-400 dark:focus:ring-zinc-600"
                    x-text="cancelText"
                >
                    Cancelar
                </button>

                <button
                    type="button"
                    @click="confirm()"
                    class="inline-flex h-8 sm:h-9 items-center justify-center gap-1.5 rounded-md px-3.5 text-xs font-medium text-white shadow-xs transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :class="{
                        'bg-red-600 hover:bg-red-700 focus:ring-red-500 dark:bg-red-600 dark:hover:bg-red-700': variant === 'danger',
                        'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500 dark:bg-amber-600 dark:hover:bg-amber-700': variant === 'warning',
                        'bg-zinc-900 hover:bg-zinc-800 focus:ring-zinc-500 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-200': variant === 'info'
                    }"
                >
                    <!-- Icono para acción destructiva -->
                    <template x-if="variant === 'danger'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </template>
                    <span x-text="confirmText"></span>
                </button>
            </div>
        </div>
    </div>
</div>
