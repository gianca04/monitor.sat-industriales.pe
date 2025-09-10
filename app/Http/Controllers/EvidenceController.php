<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Evidence;
use Illuminate\Http\Request;

class EvidenceController extends Controller
{
    /**
     * Mostrar una lista paginada de evidencias, cargando sus fotografías relacionadas.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Obtener el query builder con relaciones cargadas
        $query = Evidence::with('photos');
    
        // Filtro por búsqueda de nombre o descripción
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }
    
        // Filtro por rango de fechas (created_at)
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
    
            // Validar formato de fecha (YYYY-MM-DD)
            if (strtotime($startDate) && strtotime($endDate)) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }
    
        // Ordenamiento por fecha de creación (asc o desc)
        $sortOrder = $request->input('sort_order', 'asc'); // Valor por defecto 'asc'
        $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? $sortOrder : 'asc';
        $query->orderBy('created_at', $sortOrder);
    
        // Paginación estándar con 10 elementos por página
        $evidences = $query->paginate(10);
    
        return response()->json($evidences);
    }
    
    
    

    /**
     * Almacenar una nueva evidencia.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validación de los datos recibidos
        $validatedData = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Creación de la evidencia
        $evidence = Evidence::create($validatedData);

        return response()->json([
            'message' => 'Evidencia creada correctamente',
            'data'    => $evidence,
        ], 201);
    }

    /**
     * Mostrar una evidencia en específico, cargando sus fotografías relacionadas.
     *
     * @param  \App\Models\Evidence  $evidence
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Evidence $evidence)
    {
        // Se carga la relación "photos"
        $evidence->load('photos');
        return response()->json($evidence);
    }

    /**
     * Actualizar la información de una evidencia.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Evidence  $evidence
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Evidence $evidence)
    {
        // Validación flexible: solo se validan los campos que se envían
        $validatedData = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Actualización de la evidencia
        $evidence->update($validatedData);

        return response()->json([
            'message' => 'Evidencia actualizada correctamente',
            'data'    => $evidence,
        ]);
    }

    /**
     * Eliminar una evidencia.
     *
     * @param  \App\Models\Evidence  $evidence
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Evidence $evidence)
    {
        // En este ejemplo se elimina la evidencia. Se puede considerar eliminar también las fotos asociadas.
        $evidence->delete();

        return response()->json([
            'message' => 'Evidencia eliminada correctamente',
        ]);
    }
}
