<x-filament-panels::page>
    <div class="flex flex-col gap-y-6">
        <x-filament-panels::resources.tabs />

        @if ($this->activeTab === 'low_stock')
            @livewire(\App\Livewire\LowStockEppVariantsTable::class)
        @else
            {{ $this->table }}
        @endif
    </div>
</x-filament-panels::page>
