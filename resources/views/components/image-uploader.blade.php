@props([
    'name' => 'photo',
    'id' => null,
    'currentPhotoUrl' => null,
    'label' => 'Fotografía del Ítem',
    'badge' => 'Opcional',
    'hint' => 'Arrastra una imagen, selecciónala o pégala con Ctrl + V',
    'accept' => 'image/*',
    'maxSizeMb' => 10,
    'compact' => false,
])

@php
    $componentId = $id ?? 'uploader_' . md5($name . microtime());
@endphp

<div x-data="{
    preview: '{{ $currentPhotoUrl ?? '' }}',
    file: null,
    fileName: '',
    fileSize: '',
    isDragging: false,
    isPasting: false,
    pastedToast: false,
    errorMsg: '',

    init() {
        if (this.preview) {
            this.fileName = 'Foto actual en S3';
        }
    },

    handleFileSelect(event) {
        const files = event.target.files;
        if (files && files.length > 0) {
            this.processFile(files[0]);
        }
    },

    handleDrop(event) {
        this.isDragging = false;
        const files = event.dataTransfer?.files;
        if (files && files.length > 0) {
            this.processFile(files[0]);
        }
    },

    handlePaste(event) {
        const items = (event.clipboardData || window.clipboardData)?.items;
        if (!items) return;

        for (let i = 0; i < items.length; i++) {
            if (items[i].type.indexOf('image') !== -1) {
                const blob = items[i].getAsFile();
                if (blob) {
                    event.preventDefault();
                    const ext = blob.type.split('/')[1] || 'png';
                    const customName = `captura_${Date.now()}.${ext}`;
                    this.processFile(blob, customName);
                    this.showPastedToast();
                    break;
                }
            }
        }
    },

    processFile(file, customName = null) {
        this.errorMsg = '';
        if (!file.type.startsWith('image/')) {
            this.errorMsg = 'El archivo seleccionado debe ser una imagen válida (JPG, PNG, WEBP, GIF, SVG, etc.).';
            return;
        }

        const maxBytes = {{ (int) $maxSizeMb }} * 1024 * 1024;
        if (file.size > maxBytes) {
            this.errorMsg = `La imagen supera el límite de {{ $maxSizeMb }}MB (${this.formatBytes(file.size)}).`;
            return;
        }

        this.file = file;
        this.fileName = customName || file.name || 'imagen.png';
        this.fileSize = this.formatBytes(file.size);

        // Sincronizar el input hidden tipo file con DataTransfer
        try {
            if (this.$refs.hiddenFileInput) {
                const dataTransfer = new DataTransfer();
                const namedFile = new File([file], this.fileName, { type: file.type });
                dataTransfer.items.add(namedFile);
                this.$refs.hiddenFileInput.files = dataTransfer.files;
            }
        } catch (e) {
            // Safari / legacy fallback si DataTransfer falla
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            this.preview = e.target.result;
            this.$dispatch('photo-changed', {
                file: this.file,
                preview: this.preview,
                name: this.fileName,
                size: this.fileSize
            });
        };
        reader.readAsDataURL(file);
    },

    showPastedToast() {
        this.pastedToast = true;
        setTimeout(() => {
            this.pastedToast = false;
        }, 2200);
    },

    async pasteFromClipboardBtn() {
        if (!navigator.clipboard?.read) {
            this.errorMsg = 'Tu navegador no permite acceso directo al portapapeles. Presiona Ctrl + V.';
            return;
        }

        try {
            const clipboardItems = await navigator.clipboard.read();
            for (const item of clipboardItems) {
                const imageType = item.types.find(type => type.startsWith('image/'));
                if (imageType) {
                    const blob = await item.getType(imageType);
                    const ext = imageType.split('/')[1] || 'png';
                    this.processFile(blob, `pegado_${Date.now()}.${ext}`);
                    this.showPastedToast();
                    return;
                }
            }
            this.errorMsg = 'No hay ninguna imagen en el portapapeles. Copia una imagen y vuelve a intentar.';
        } catch (err) {
            this.errorMsg = 'Presiona Ctrl + V dentro del área para pegar tu imagen.';
        }
    },

    clearPhoto() {
        this.preview = null;
        this.file = null;
        this.fileName = '';
        this.fileSize = '';
        this.errorMsg = '';
        if (this.$refs.hiddenFileInput) {
            this.$refs.hiddenFileInput.value = '';
        }
        this.$dispatch('photo-removed');
        this.$dispatch('photo-changed', { file: null, preview: null });
    },

    triggerSelect() {
        this.$refs.hiddenFileInput.click();
    },

    formatBytes(bytes) {
        if (!bytes || bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }
}" id="{{ $componentId }}" class="w-full space-y-2 select-none"
    @paste.window="handlePaste($event)" @reset-photo.window="clearPhoto()"
    @set-photo-url.window="preview = $event.detail.url; fileName = 'Foto asignada'">

    <!-- Encabezado con Label y Badge estilo Shadcn -->
    @if ($label)
        <div class="flex items-center justify-between">
            <label class="text-xs font-medium text-zinc-900 dark:text-zinc-100 flex items-center gap-1.5">
                <span>{{ $label }}</span>
                <span class="inline-flex items-center gap-1 text-[10px] text-zinc-400 font-normal">
                    <span>Formatos: PNG, JPG, WEBP, GIF, SVG.</span>
                </span>
            </label>
            @if ($badge)
                <span
                    class="text-[10px] font-medium text-zinc-500 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-800/80 px-2 py-0.5 rounded-full border border-zinc-200/60 dark:border-zinc-700/60">
                    {{ $badge }}
                </span>
            @endif
        </div>
    @endif

    <!-- Input file nativo (oculto) -->
    <input type="file" name="{{ $name }}" x-ref="hiddenFileInput" accept="{{ $accept }}"
        @change="handleFileSelect($event)" class="sr-only hidden" />

    <!-- Notificación emergente estilo Shadcn Toast al pegar -->
    <div x-show="pastedToast" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 text-[11px] font-medium text-emerald-800 dark:text-emerald-300 shadow-sm"
        style="display: none;">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"
            viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd"
                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                clip-rule="evenodd" />
        </svg>
        <span>¡Imagen pegada desde el portapapeles exitosamente!</span>
    </div>

    <!-- Mensaje de error de validación -->
    <div x-show="errorMsg" x-transition
        class="p-2 rounded-md bg-red-50 dark:bg-red-950/50 border border-red-200 dark:border-red-800 text-[11px] text-red-700 dark:text-red-300 flex items-center justify-between"
        style="display: none;">
        <div class="flex items-center gap-1.5">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-red-500" viewBox="0 0 20 20"
                fill="currentColor">
                <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd" />
            </svg>
            <span x-text="errorMsg"></span>
        </div>
        <button type="button" @click="errorMsg = ''" class="text-red-500 hover:text-red-700 p-0.5">
            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <!-- ESTADO 1: Dropzone y Zona de Captura (Cuando NO hay imagen cargada) -->
    <div x-show="!preview" @click="triggerSelect()" @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false" @drop.prevent="handleDrop($event)"
        :class="{
            'border-zinc-950 dark:border-zinc-200 bg-zinc-100/80 dark:bg-zinc-900/80 ring-2 ring-zinc-900/10 dark:ring-zinc-100/10': isDragging,
            'border-zinc-300/80 dark:border-zinc-800 bg-zinc-50/40 dark:bg-zinc-950/40 hover:bg-zinc-100/50 dark:hover:bg-zinc-900/50 hover:border-zinc-400 dark:hover:border-zinc-700':
                !isDragging
        }"
        class="group relative flex flex-col items-center justify-center p-4 border border-dashed rounded-lg cursor-pointer transition-all duration-150 text-center">

        <!-- Texto de instrucción principal -->
        <p class="text-xs font-medium text-zinc-800 dark:text-zinc-200">
            Haz clic o arrastra una imagen aquí
        </p>

        <!-- Indicador de Pegar desde el Portapapeles -->
        <div class="mt-1 flex items-center gap-1.5 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span>o pega directamente con</span>
            <kbd
                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-zinc-200/80 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-300/80 dark:border-zinc-700 shadow-[0_1px_0_rgba(0,0,0,0.06)]">
                Ctrl + V
            </kbd>
        </div>

    </div>

    <!-- ESTADO 2: Visualizador de Fotografía (Preview Activo) -->
    <div x-show="preview"
        class="relative border border-zinc-200 dark:border-zinc-800 rounded-lg overflow-hidden bg-zinc-50/70 dark:bg-zinc-950 transition-all shadow-sm"
        style="display: none;">

        <!-- Área de la imagen con checkerboard de transparencia sutil -->
        <div
            class="relative w-full h-40 sm:h-44 flex items-center justify-center bg-zinc-100/50 dark:bg-zinc-900/40 p-2 overflow-hidden">
            <img :src="preview" alt="Vista previa del ítem"
                class="max-h-full max-w-full object-contain rounded-md shadow-sm transition-transform hover:scale-[1.02] duration-200" />

            <!-- Badge flotante de estado -->
            <div
                class="absolute top-2 left-2 flex items-center gap-1 px-2 py-0.5 rounded-full bg-zinc-950/75 dark:bg-zinc-900/90 backdrop-blur-sm text-[10px] font-medium text-white border border-white/10 shadow-sm">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span x-text="fileName || 'Imagen cargada'"></span>
                <span x-show="fileSize" class="text-zinc-400 text-[9px]" x-text="'(' + fileSize + ')'"></span>
            </div>
        </div>

        <!-- Barra inferior de acciones estilo Shadcn -->
        <div
            class="flex items-center justify-end px-3 py-1.5 border-t border-zinc-200/80 dark:border-zinc-800 bg-white dark:bg-zinc-900">
            <!-- Botón Eliminar / Quitar -->
            <button type="button" @click="clearPhoto()"
                class="inline-flex items-center gap-1 h-7 px-2 rounded-md text-[11px] font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 hover:text-red-700 transition-colors"
                title="Quitar esta fotografía">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                <span>Quitar</span>
            </button>
        </div>
    </div>
</div>
