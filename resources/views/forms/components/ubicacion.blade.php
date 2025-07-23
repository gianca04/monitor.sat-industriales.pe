{{-- resources/views/forms/components/ubicacion.blade.php --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div x-data="{
        value: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
        get lat() { return this.value?.latitude ?? 0 },
        set lat(val) {
            this.value = {
                latitude: parseFloat(val) || 0,
                longitude: this.value?.longitude ?? 0,
                location: this.value?.location ?? ''
            }
        },
        get lng() { return this.value?.longitude ?? 0 },
        set lng(val) {
            this.value = {
                latitude: this.value?.latitude ?? 0,
                longitude: parseFloat(val) || 0,
                location: this.value?.location ?? ''
            }
        },
        get location() { return this.value?.location ?? '' },
        set location(val) {
            this.value = {
                latitude: this.value?.latitude ?? 0,
                longitude: this.value?.longitude ?? 0,
                location: val
            }
        },
        map: null,
        marker: null,
        search: '',
        decodeValue() {
            if (typeof this.value === 'string' && this.value.trim().startsWith('{')) {
                try {
                    const obj = JSON.parse(this.value);
                    this.value = {
                        latitude: obj.latitude ?? 0,
                        longitude: obj.longitude ?? 0,
                        location: obj.location ?? ''
                    };
                } catch (e) {}
            }
        },
        async buscarLugar() {
            if (!this.search) return;
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.search)}`);
            const lugares = await response.json();
            if (lugares.length > 0) {
                this.lat = parseFloat(lugares[0].lat);
                this.lng = parseFloat(lugares[0].lon);
                this.location = lugares[0].display_name;
                this.map.setView([this.lat, this.lng], 15);
                this.marker.setLatLng([this.lat, this.lng]);
            } else {
                alert('Lugar no encontrado');
            }
        },
        async reverseGeocode() {
            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${this.lat}&lon=${this.lng}`);
            const data = await response.json();
            this.location = data.display_name || '';
        },
        init() {
            this.decodeValue();
            this.map = L.map('map').setView([this.lat, this.lng], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(this.map);

            this.marker = L.marker([this.lat, this.lng], { draggable: true }).addTo(this.map);

            this.marker.on('dragend', async () => {
                const position = this.marker.getLatLng();
                this.lat = position.lat;
                this.lng = position.lng;
                await this.reverseGeocode();
            });

            this.map.on('click', async (e) => {
                this.lat = e.latlng.lat;
                this.lng = e.latlng.lng;
                this.marker.setLatLng([this.lat, this.lng]);
                await this.reverseGeocode();
            });

            this.$watch('lat', value => this.marker.setLatLng([value, this.lng]));
            this.$watch('lng', value => this.marker.setLatLng([this.lat, value]));
        }
    }" x-init="init()">

        {{-- Input búsqueda --}}
        <div class="search-bar">
            <input type="text" x-model="search" placeholder="Buscar lugar..." class="search-input">
            <a href="#" class="search-btn" @click.prevent="buscarLugar()">Buscar</a>
        </div>

        {{-- Dirección actual --}}
        <div class="mb-4">
            <input type="text" x-model="location" placeholder="Dirección"
                class="w-full px-3 py-2 my-2 bg-gray-100 border border-gray-300 rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600"
                readonly>
            <input wire:model="{{ $getStatePath() }}" type="hidden" x-model="JSON.stringify(value)" />
        </div>

        {{-- Mapa --}}
        <div class="map-container" style="position: relative;">
            <div id="map" class="w-full my-4 border border-gray-300 rounded-lg shadow-sm h-96 dark:border-gray-600">
            </div>
        </div>
        {{-- Coordenadas --}}
        <div class="flex gap-3 my-4">
            <input type="text" x-model="lat" placeholder="Latitud"
                class="w-1/2 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600"
                readonly>
            <input type="text" x-model="lng" placeholder="Longitud"
                class="w-1/2 px-3 py-2 bg-gray-100 border border-gray-300 rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600"
                readonly>
        </div>

    </div>
    <style>
        .map-container {
            width: 100%;
            min-height: 350px;
            position: relative;
            z-index: 0;
        }

        #map {
            min-height: 350px;
            height: 24rem;
            /* h-96 */
            z-index: 1;
            border-radius: 0.5rem;
        }

        /* Asegura que los popups y controles estén por encima */
        .leaflet-top,
        .leaflet-bottom {
            z-index: 2 !important;
        }

        .search-bar {
            display: flex;
            align-items: stretch;
            margin-bottom: 1rem;
            gap: 0;
        }

        .search-input {
            flex: 1 1 0%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem 0 0 0.5rem;
            font-size: 1rem;
            background: #f9fafb;
            color: #222;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            border-color: #2563eb;
            background: #fff;
        }

        .search-btn {
            display: inline-flex;
            align-items: center;
            padding: 0 1.25rem;
            background: #2563eb;
            color: #fff;
            font-weight: 600;
            border: 1px solid #2563eb;
            border-radius: 0 0.5rem 0.5rem 0;
            font-size: 1rem;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }

        .search-btn:hover,
        .search-btn:focus {
            background: #1d4ed8;
            color: #fff;
        }
    </style>
</x-dynamic-component>
