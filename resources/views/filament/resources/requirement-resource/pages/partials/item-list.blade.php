@php
    $initialItems = [];
    if (isset($record) && $record) {
        $record->loadMissing(['requirementLists.item.unit']);
        foreach ($record->requirementLists as $reqList) {
            if ($reqList->item) {
                $initialItems[] = [
                    'id' => $reqList->item->id,
                    'name' => $reqList->item->name,
                    'sku' => $reqList->item->sku ?? '',
                    'unit' => $reqList->item->unit ? $reqList->item->unit->symbol ?? $reqList->item->unit->name : 'UND',
                    'quantity' => (float) $reqList->quantity,
                ];
            }
        }
    }
@endphp

<div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-950 flex flex-col h-full"
    x-data="{
        items: @js($initialItems),
        activeMenuIndex: null,
    
        addItem(item) {
            if (!item || !item.id) return;
            const existing = this.items.find(i => i.id === item.id);
            if (existing) {
                existing.quantity = Math.round(((parseFloat(existing.quantity) || 0) + 1) * 100) / 100;
            } else {
                this.items.push({
                    id: item.id,
                    name: item.name,
                    sku: item.sku || '',
                    unit: item.unit ? (item.unit.symbol || item.unit.name) : 'UND',
                    quantity: 1
                });
            }
            this.notifyChange();
        },
    
        removeItem(index) {
            this.items.splice(index, 1);
            this.activeMenuIndex = null;
            this.notifyChange();
        },
    
        duplicateItem(index) {
            const original = this.items[index];
            if (original) {
                this.items.splice(index + 1, 0, {
                    id: original.id,
                    name: original.name,
                    sku: original.sku,
                    unit: original.unit,
                    quantity: 1
                });
            }
            this.activeMenuIndex = null;
            this.notifyChange();
        },
    
        updateQuantity(index, value) {
            const num = parseFloat(value);
            if (isNaN(num) || num <= 0) {
                this.items[index].quantity = 1;
            } else {
                this.items[index].quantity = Math.round(num * 100) / 100;
            }
            this.notifyChange();
        },
    
        increment(index) {
            const current = parseFloat(this.items[index].quantity) || 0;
            this.items[index].quantity = Math.round((current + 1) * 100) / 100;
            this.notifyChange();
        },
    
        decrement(index) {
            const current = parseFloat(this.items[index].quantity) || 1;
            if (current > 1) {
                this.items[index].quantity = Math.round((current - 1) * 100) / 100;
                this.notifyChange();
            }
        },
    
        clearAll() {
            if (!this.items || this.items.length === 0) return;

            const count = this.items.length;
            this.$dispatch('open-confirm-modal', {
                title: '¿Vaciar todos los ítems agregados?',
                description: `Se ${count === 1 ? 'eliminará el único material agregado' : 'eliminarán todos los ' + count + ' materiales agregados'} de esta solicitud. Esta acción no se puede deshacer.`,
                confirmText: 'Sí, vaciar lista',
                cancelText: 'Cancelar',
                variant: 'danger',
                action: 'clear-all-items'
            });
        },

        clearAllConfirmed() {
            this.items = [];
            this.activeMenuIndex = null;
            this.notifyChange();
        },
    
        toggleMenu(index) {
            this.activeMenuIndex = this.activeMenuIndex === index ? null : index;
        },
    
        totalQuantity() {
            const sum = this.items.reduce((acc, curr) => acc + (parseFloat(curr.quantity) || 0), 0);
            return Math.round(sum * 100) / 100;
        },
    
        notifyChange() {
            this.$dispatch('requirement-items-updated', this.items);
        }
    }" @item-selected.window="addItem($event.detail)" @click.outside="activeMenuIndex = null"
    @keydown.escape.window="activeMenuIndex = null"
    @confirm-action-confirmed.window="if ($event.detail?.action === 'clear-all-items') clearAllConfirmed()">
    <!-- Card Header (Shadcn style) -->
    <div class="flex items-center justify-between p-4 border-b border-zinc-100 dark:border-zinc-900">
        <div class="flex items-center gap-2">
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 leading-none">
                    Ítems del Requerimiento
                </h3>
                <p class="text-[11px] text-zinc-400 mt-1">
                    Materiales y cantidades solicitadas
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400"
                x-text="items.length + (items.length === 1 ? ' ítem agregado' : ' ítems agregados')">
            </span>

            <template x-if="items.length > 0">
                <button type="button" @click="clearAll()"
                    class="text-[11px] font-medium text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                    Vaciar lista
                </button>
            </template>
        </div>
    </div>

    <!-- Table Container -->
    <div class="flex-1 overflow-x-auto min-h-[360px]">
        <!-- Empty State -->
        <template x-if="items.length === 0">
            <div class="flex flex-col items-center justify-center h-full py-16 px-4 text-center">
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-900 text-zinc-400 mb-3 border border-zinc-200/60 dark:border-zinc-800">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <h4 class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    No hay materiales en la lista
                </h4>
                <p class="text-xs text-zinc-400 mt-1 max-w-sm">
                    Selecciona ítems del catálogo o crea uno nuevo para incorporarlo a esta solicitud.
                </p>
            </div>
        </template>

        <!-- Table with Items -->
        <template x-if="items.length > 0">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr
                        class="border-b border-zinc-100 dark:border-zinc-900 bg-zinc-50/50 dark:bg-zinc-900/20 text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                        <th class="py-2.5 px-4">Item</th>
                        <th class="py-2.5 px-3 text-center w-28">Unidad</th>
                        <th class="py-2.5 px-3 text-center w-36">Cantidad</th>
                        <th class="py-2.5 px-4 text-right w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-900 text-xs">
                    <template x-for="(item, index) in items" :key="item.id + '-' + index">
                        <tr class="group hover:bg-zinc-50/60 dark:hover:bg-zinc-900/40 transition-colors">
                            <!-- Col 1: Item Name (con SKU) -->
                            <td class="py-3 px-4">
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100 leading-snug"
                                        x-text="item.name"></span>
                                    <span class="text-[10px] text-zinc-400 mt-0.5"
                                        x-text="item.sku ? 'SKU: ' + item.sku : 'Sin SKU'"></span>
                                </div>
                            </td>

                            <!-- Col 2: Unit Symbol -->
                            <td class="py-3 px-3 text-center">
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                    x-text="item.unit || 'UND'"></span>
                            </td>

                            <!-- Col 3: Cantidad Input Editable con Stepper -->
                            <td class="py-3 px-3 text-center">
                                <div class="inline-flex items-center justify-center">
                                    <button type="button" @click="decrement(index)"
                                        class="h-8 w-7 inline-flex items-center justify-center rounded-l-md border border-r-0 border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors select-none text-xs"
                                        title="Reducir cantidad">
                                        -
                                    </button>
                                    <input type="number" step="any" min="0.01" :value="item.quantity"
                                        @input="updateQuantity(index, $event.target.value)"
                                        class="h-8 w-16 text-center text-xs font-medium border-y border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-950 text-zinc-900 dark:text-zinc-100 focus:outline-none focus:border-zinc-900 dark:focus:border-zinc-400 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" />
                                    <button type="button" @click="increment(index)"
                                        class="h-8 w-7 inline-flex items-center justify-center rounded-r-md border border-l-0 border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors select-none text-xs"
                                        title="Aumentar cantidad">
                                        +
                                    </button>
                                </div>
                            </td>

                            <!-- Col 4: Icono con 3 puntos (Menu Shadcn) -->
                            <td class="py-3 px-4 text-right">
                                <div class="relative inline-flex justify-end">
                                    <button type="button" @click.stop="toggleMenu(index)"
                                        class="p-1.5 rounded-md text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors"
                                        title="Opciones">
                                        <!-- Icono horizontal 3 puntos -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 12h.01M12 12h.01M19 12h.01M6 12a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0zm7 0a1 1 0 11-2 0 1 1 0 012 0z" />
                                        </svg>
                                    </button>

                                    <!-- Shadcn Dropdown Menu -->
                                    <div x-show="activeMenuIndex === index" x-cloak
                                        @click.outside="if (activeMenuIndex === index) activeMenuIndex = null"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="absolute right-0 top-full mt-1 z-30 w-36 rounded-md border border-zinc-200 bg-white p-1 text-xs shadow-lg dark:border-zinc-800 dark:bg-zinc-950">
                                        <button type="button" @click="duplicateItem(index)"
                                            class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-900 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-zinc-400"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <span>Duplicar</span>
                                        </button>
                                        <div class="my-1 border-t border-zinc-100 dark:border-zinc-900"></div>
                                        <button type="button" @click="removeItem(index)"
                                            class="flex w-full items-center gap-2 rounded-sm px-2 py-1.5 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-red-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Eliminar</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </template>
    </div>

    <!-- Card Footer Summary -->
    <template x-if="items.length > 0">
        <div
            class="flex items-center justify-between p-3.5 border-t border-zinc-100 dark:border-zinc-900 bg-zinc-50/40 dark:bg-zinc-900/20 text-xs">
            <span class="text-zinc-500 dark:text-zinc-400">
                Total líneas: <strong class="font-medium text-zinc-900 dark:text-zinc-100"
                    x-text="items.length"></strong>
            </span>
            <span class="text-zinc-500 dark:text-zinc-400">
                Cantidad total: <strong class="font-medium text-zinc-900 dark:text-zinc-100"
                    x-text="totalQuantity()"></strong>
            </span>
        </div>
    </template>
</div>
