<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Photo;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    /**
     * Mostrar una lista paginada de fotografías.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        // Se incluye la relación "evidence" para optimizar las consultas.
        $photos = Photo::with('evidence')->paginate(10);
        return response()->json($photos);
    }

    /**
     * Almacenar una nueva fotografía y subir la imagen al servidor.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validación de los datos recibidos, se valida que se trate de un archivo de imagen.
        $validatedData = $request->validate([
            'evidence_id' => 'required|exists:evidences,id',
            'photo'       => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4076',
            'descripcion' => 'nullable|string',
        ]);

        // Se obtiene el archivo de imagen.
        $file = $request->file('photo');
        // Se genera un nombre único para la imagen.
        $filename = time() . '_' . $file->getClientOriginalName();
        // Se define la ruta de destino dentro de la carpeta public.
        $destinationPath = public_path('uploads/photos');
        // Se mueve el archivo a la ubicación definida.
        $file->move($destinationPath, $filename);

        // Se establece la ruta relativa de la imagen para almacenar en la base de datos.
        $filePath = 'uploads/photos/' . $filename;

        // Creación de la fotografía utilizando asignación masiva.
        $photo = Photo::create([
            'evidence_id' => $validatedData['evidence_id'],
            'photo_path'  => $filePath,
            'descripcion' => $validatedData['descripcion'] ?? null,
        ]);

        return response()->json([
            'message' => 'Foto creada correctamente',
            'data'    => $photo,
        ], 201);
    }

    /**
     * Mostrar una fotografía en específico.
     *
     * @param  \App\Models\Photo  $photo
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Photo $photo)
    {
        // Cargamos la relación "evidence" para obtener la información relacionada.
        $photo->load('evidence');
        return response()->json($photo);
    }

    /**
     * Actualizar la información de una fotografía, incluyendo la opción de subir una nueva imagen.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Photo  $photo
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Photo $photo)
    {
        // Validación flexible: se valida el archivo de imagen si es que se envía.
        $validatedData = $request->validate([
            'evidence_id' => 'sometimes|required|exists:evidences,id',
            'photo'       => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'descripcion' => 'nullable|string',
        ]);

        // Si se envía un nuevo archivo de imagen, se procesa la subida.
        if ($request->hasFile('photo')) {
            // Opcional: se puede eliminar la imagen antigua del servidor.
            $oldPath = public_path($photo->photo_path);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
            $file = $request->file('photo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $destinationPath = public_path('uploads/photos');
            $file->move($destinationPath, $filename);
            $validatedData['photo_path'] = 'uploads/photos/' . $filename;
        }

        $photo->update($validatedData);

        return response()->json([
            'message' => 'Foto actualizada correctamente',
            'data'    => $photo,
        ]);
    }

    /**
     * Eliminar una fotografía.
     *
     * @param  \App\Models\Photo  $photo
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Photo $photo)
    {
        // Opcional: eliminar el archivo físico de la imagen.
        $filePath = public_path($photo->photo_path);
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $photo->delete();

        return response()->json([
            'message' => 'Foto eliminada correctamente'
        ]);
    }
}
