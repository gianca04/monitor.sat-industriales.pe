<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Position::query();

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where('name', 'like', $searchTerm);
        }

        // Ordenamiento
        $sortBy = $request->sort_by ?? 'name';
        $sortOrder = $request->sort_order ?? 'asc';

        if (in_array($sortBy, ['id', 'name', 'created_at', 'updated_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        // Paginación
        $positions = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Posiciones obtenidas exitosamente',
            'data' => $positions->items(),
            'pagination' => [
                'total' => $positions->total(),
                'perPage' => $positions->perPage(),
                'currentPage' => $positions->currentPage(),
                'lastPage' => $positions->lastPage(),
                'from' => $positions->firstItem(),
                'to' => $positions->lastItem(),
                'hasMorePages' => $positions->hasMorePages(),
            ],
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePositionRequest $request): JsonResponse
    {
        $position = Position::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Posición creada exitosamente',
            'data' => $position,
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Position $position): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Posición obtenida exitosamente',
            'data' => $position,
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePositionRequest $request, Position $position): JsonResponse
    {
        $position->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Posición actualizada exitosamente',
            'data' => $position,
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position): JsonResponse
    {
        // Verificar si tiene empleados asociados
        if ($position->employees()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la posición porque tiene empleados asociados.',
                'meta' => [
                    'apiVersion' => '1.0',
                    'timestamp' => now()->utc()->toIso8601String(),
                ],
            ], 422);
        }

        $position->delete();

        return response()->json([
            'success' => true,
            'message' => 'Posición eliminada exitosamente',
            'data' => ['id' => $position->id],
            'meta' => [
                'apiVersion' => '1.0',
                'timestamp' => now()->utc()->toIso8601String(),
            ],
        ], 200);
    }
}