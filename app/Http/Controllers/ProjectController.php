<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    /**
     * Obtener todos los proyectos vigentes
     */
    public function index(): JsonResponse
    {
        try {
            $projects = Project::with(['quote.client'])
                ->where('start_date', '<=', Carbon::now())
                ->where('end_date', '>=', Carbon::now())
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $projects,
                'message' => 'Proyectos obtenidos correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los proyectos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener un proyecto específico
     */
    public function show($id): JsonResponse
    {
        try {
            $project = Project::with(['quote.client', 'timesheets.attendances'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $project,
                'message' => 'Proyecto obtenido correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Proyecto no encontrado'
            ], 404);
        }
    }

    /**
     * Obtener todos los proyectos (incluyendo los no vigentes)
     */
    public function all(Request $request): JsonResponse
    {
        try {
            $query = Project::with(['quote.client']);

            // Filtrar por rango de fechas si se especifica
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = Carbon::parse($request->start_date);
                $endDate = Carbon::parse($request->end_date);

                $query->where(function($q) use ($startDate, $endDate) {
                    // Proyectos que se superponen con el rango solicitado
                    $q->where('start_date', '<=', $endDate)
                      ->where('end_date', '>=', $startDate);
                });
            }

            // Filtrar por fecha específica
            if ($request->has('date')) {
                $date = Carbon::parse($request->date);
                $query->where('start_date', '<=', $date)
                      ->where('end_date', '>=', $date);
            }

            // Búsqueda por nombre
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('name', 'like', "%{$search}%");
            }

            // Filtrar por estado del proyecto
            if ($request->has('status')) {
                $today = Carbon::today();
                switch ($request->status) {
                    case 'active':
                        $query->where('start_date', '<=', $today)
                              ->where('end_date', '>=', $today);
                        break;
                    case 'upcoming':
                        $query->where('start_date', '>', $today);
                        break;
                    case 'finished':
                        $query->where('end_date', '<', $today);
                        break;
                }
            }

            $projects = $query->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $projects,
                'message' => 'Proyectos obtenidos correctamente',
                'filters_applied' => [
                    'start_date' => $request->start_date ?? null,
                    'end_date' => $request->end_date ?? null,
                    'date' => $request->date ?? null,
                    'search' => $request->search ?? null,
                    'status' => $request->status ?? null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los proyectos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Búsqueda avanzada de proyectos con múltiples filtros
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'nullable|string',
                'client_id' => 'nullable|exists:clients,id',
                'start_date_from' => 'nullable|date',
                'start_date_to' => 'nullable|date|after_or_equal:start_date_from',
                'end_date_from' => 'nullable|date',
                'end_date_to' => 'nullable|date|after_or_equal:end_date_from',
                'active_on_date' => 'nullable|date',
                'location' => 'nullable|string',
                'status' => 'nullable|in:active,upcoming,finished,all'
            ]);

            $query = Project::with(['quote.client', 'timesheets']);

            // Filtro por nombre
            if ($request->filled('name')) {
                $query->where('name', 'like', '%' . $request->name . '%');
            }

            // Filtro por cliente
            if ($request->filled('client_id')) {
                $query->whereHas('quote', function($q) use ($request) {
                    $q->where('client_id', $request->client_id);
                });
            }

            // Filtro por rango de fecha de inicio
            if ($request->filled('start_date_from')) {
                $query->where('start_date', '>=', Carbon::parse($request->start_date_from));
            }
            if ($request->filled('start_date_to')) {
                $query->where('start_date', '<=', Carbon::parse($request->start_date_to));
            }

            // Filtro por rango de fecha de fin
            if ($request->filled('end_date_from')) {
                $query->where('end_date', '>=', Carbon::parse($request->end_date_from));
            }
            if ($request->filled('end_date_to')) {
                $query->where('end_date', '<=', Carbon::parse($request->end_date_to));
            }

            // Filtro por proyectos activos en una fecha específica
            if ($request->filled('active_on_date')) {
                $date = Carbon::parse($request->active_on_date);
                $query->where('start_date', '<=', $date)
                      ->where('end_date', '>=', $date);
            }

            // Filtro por ubicación
            if ($request->filled('location')) {
                $query->where('location', 'like', '%' . $request->location . '%');
            }

            // Filtro por estado
            if ($request->filled('status') && $request->status !== 'all') {
                $today = Carbon::today();
                switch ($request->status) {
                    case 'active':
                        $query->where('start_date', '<=', $today)
                              ->where('end_date', '>=', $today);
                        break;
                    case 'upcoming':
                        $query->where('start_date', '>', $today);
                        break;
                    case 'finished':
                        $query->where('end_date', '<', $today);
                        break;
                }
            }

            $projects = $query->orderBy('start_date', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $projects,
                'message' => 'Búsqueda de proyectos completada',
                'total_found' => $projects->count(),
                'filters_applied' => $request->only([
                    'name', 'client_id', 'start_date_from', 'start_date_to',
                    'end_date_from', 'end_date_to', 'active_on_date', 'location', 'status'
                ])
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error en la búsqueda: ' . $e->getMessage()
            ], 500);
        }
    }
}
