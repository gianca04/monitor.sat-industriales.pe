<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePhotoRequest;
use App\Http\Requests\UpdatePhotoRequest;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    /**
     * Listar fotos (Opcionalmente filtradas por reporte).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Photo::query();

        if ($request->has('work_report_id')) {
            $query->where('work_report_id', $request->work_report_id);
        }

        // Paginación estándar de Laravel
        $photos = $query->latest()->paginate($request->per_page ?? 20);

        // Formatear los datos
        $data = $photos->map(function ($photo) {
            return $this->formatPhoto($photo);
        });

        return response()->json([
            'success' => true,
            'message' => 'Fotos obtenidas exitosamente',
            'data' => $data,
            'pagination' => [
                'total' => $photos->total(),
                'perPage' => $photos->perPage(),
                'currentPage' => $photos->currentPage(),
                'lastPage' => $photos->lastPage(),
                'from' => $photos->firstItem(),
                'to' => $photos->lastItem(),
                'hasMorePages' => $photos->hasMorePages(),
            ],
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Guardar una nueva foto.
     */
    public function store(StorePhotoRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Manejo de la foto principal
        if ($request->hasFile('photo')) {
            // Guardamos en disco 'public', carpeta 'photos'
            $data['photo_path'] = $request->file('photo')->store('photos', 'public');
        }

        // Manejo de la foto "antes"
        if ($request->hasFile('before_work_photo')) {
            $data['before_work_photo_path'] = $request->file('before_work_photo')->store('photos/before', 'public');
        }

        // Creamos el registro
        $photo = Photo::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Foto creada correctamente',
            'data' => $this->formatPhoto($photo),
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Mostrar una foto específica.
     */
    public function show(Photo $photo): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Foto obtenida exitosamente',
            'data' => $this->formatPhoto($photo),
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Actualizar una foto existente.
     * 
     * Este método es VERSÁTIL y permite actualizar cualquier combinación de campos:
     * - Solo descripción principal
     * - Solo descripción "before"
     * - Solo foto principal (elimina la anterior automáticamente)
     * - Solo foto "before" (elimina la anterior automáticamente)
     * - Cualquier combinación de los campos anteriores
     * 
     * IMPORTANTE: Usar POST con _method=PUT/PATCH cuando se envían archivos
     * 
     * @param UpdatePhotoRequest $request - Validación flexible con 'sometimes'
     * @param Photo $photo - Modelo cargado automáticamente por route model binding
     * @return JsonResponse
     */
    public function update(UpdatePhotoRequest $request, Photo $photo): JsonResponse
    {
        // Obtenemos solo los campos que fueron enviados en la petición
        $data = $request->validated();

        // MANEJO DE FOTO PRINCIPAL: Solo si se envía un nuevo archivo
        if ($request->hasFile('photo')) {
            // 1. Eliminar foto anterior del almacenamiento si existe
            if ($photo->photo_path && Storage::disk('public')->exists($photo->photo_path)) {
                Storage::disk('public')->delete($photo->photo_path);
            }
            // 2. Guardar nueva foto y actualizar la ruta en los datos
            $data['photo_path'] = $request->file('photo')->store('photos', 'public');
        }

        // MANEJO DE FOTO "ANTES DEL TRABAJO": Solo si se envía un nuevo archivo
        if ($request->hasFile('before_work_photo')) {
            // 1. Eliminar foto anterior del almacenamiento si existe
            if ($photo->before_work_photo_path && Storage::disk('public')->exists($photo->before_work_photo_path)) {
                Storage::disk('public')->delete($photo->before_work_photo_path);
            }
            // 2. Guardar nueva foto y actualizar la ruta en los datos
            $data['before_work_photo_path'] = $request->file('before_work_photo')->store('photos/before', 'public');
        }

        // Actualizar solo los campos que fueron enviados
        $photo->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Foto actualizada correctamente',
            'data' => $this->formatPhoto($photo),
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Eliminar una foto y sus archivos.
     */
    public function destroy(Photo $photo): JsonResponse
    {
        try {
            // Eliminamos los archivos físicos para no dejar basura en el servidor
            if ($photo->photo_path && Storage::disk('public')->exists($photo->photo_path)) {
                Storage::disk('public')->delete($photo->photo_path);
            }

            if ($photo->before_work_photo_path && Storage::disk('public')->exists($photo->before_work_photo_path)) {
                Storage::disk('public')->delete($photo->before_work_photo_path);
            }

            $id = $photo->id;
            $photo->delete();

            return response()->json([
                'success' => true,
                'message' => 'Foto eliminada correctamente',
                'data' => ['id' => $id],
                'meta' => [
                    'apiVersion' => '1.0',
                    'timestamp' => now()->utc()->toIso8601String(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la foto: ' . $e->getMessage(),
                'meta' => [
                    'apiVersion' => '1.0',
                    'timestamp' => now()->utc()->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * Formatear una foto con estructura estandarizada
     */
    private function formatPhoto($photo)
    {
        return [
            'id' => $photo->id,
            'work_report_id' => $photo->work_report_id,
            'photo_path' => $photo->photo_path ? url(Storage::url($photo->photo_path)) : null,
            'descripcion' => $photo->descripcion ?? '',
            'before_work_photo_path' => $photo->before_work_photo_path ? url(Storage::url($photo->before_work_photo_path)) : null,
            'before_work_descripcion' => $photo->before_work_descripcion ?? '',
            'created_at' => $photo->created_at?->toIso8601String(),
            'updated_at' => $photo->updated_at?->toIso8601String(),
        ];
    }
}
