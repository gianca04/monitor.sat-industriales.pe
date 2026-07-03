<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        try {
            $projects = Project::with([
                'quote.client',
                'quote.sub_client',
                'subClient.client'
            ])
                ->withCount('workReports')
                // ->where('start_date', '<=', now())
                // ->where('end_date', '>=', now())
                ->when($request->query('name'), function ($q, $name) {
                    $q->where('name', 'like', "%$name%");
                })
                ->when($request->query('client_name'), function ($q, $clientName) {
                    $q->where(function ($sub) use ($clientName) {
                        $sub->whereHas('quote.client', function ($subQ) use ($clientName) {
                            $subQ->where('business_name', 'like', "%$clientName%");
                        })->orWhereHas('subClient.client', function ($subQ) use ($clientName) {
                            $subQ->where('business_name', 'like', "%$clientName%");
                        });
                    });
                })
                ->when($request->query('subclient_name'), function ($q, $subclientName) {
                    $q->where(function ($sub) use ($subclientName) {
                        $sub->whereHas('quote.sub_client', function ($subQ) use ($subclientName) {
                            $subQ->where('name', 'like', "%$subclientName%");
                        })->orWhereHas('subClient', function ($subQ) use ($subclientName) {
                            $subQ->where('name', 'like', "%$subclientName%");
                        });
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $data = $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date,
                    'end_date' => $project->end_date,
                    'tdr' => $project->quote ? $project->quote->TDR : null,
                    'quote_id' => $project->quote ? $project->quote->id : null,
                    'reports_count' => $project->work_reports_count ?? 0,
                    'client' => ($project->quote && $project->quote->client)
                        ? [
                            'id' => $project->quote->client->id,
                            'business_name' => $project->quote->client->business_name,
                        ]
                        : (($project->subClient && $project->subClient->client)
                            ? [
                                'id' => $project->subClient->client->id,
                                'business_name' => $project->subClient->client->business_name,
                            ]
                            : null),
                    'sub_client' => ($project->quote && $project->quote->sub_client)
                        ? [
                            'id' => $project->quote->sub_client->id,
                            'name' => $project->quote->sub_client->name,
                        ]
                        : ($project->subClient
                            ? [
                                'id' => $project->subClient->id,
                                'name' => $project->subClient->name,
                            ]
                            : null),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Proyectos obtenidos correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los proyectos'
            ], 500);
        }
    }

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

    public function quickSearch(Request $request): JsonResponse
    {
        Log::info('quickSearch: Petición recibida desde el front', ['query_params' => $request->all()]);

        try {
            $request->validate([
                'query' => 'nullable|string|max:100'
            ]);

            $queryStr = $request->input('query');
            Log::info('quickSearch: Validación pasada, queryStr: ' . $queryStr);

            $query = Project::query();

            if ($queryStr) {
                $query->where('name', 'like', "%{$queryStr}%");
            }

            $projects = $query->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($project) {
                    return [
                        'id' => $project->id,
                        'name' => $project->name,
                    ];
                });

            Log::info('quickSearch: Query ejecutada, proyectos encontrados: ' . count($projects));

            return response()->json([
                "success" => true,
                "message" => "Búsqueda rápida completada",
                "data" => $projects,
                "meta" => [
                    "apiVersion" => "1.0",
                    "timestamp" => now()->toIso8601String()
                ]
            ]);
        } catch (ValidationException $e) {
            Log::info('quickSearch: Error de validación', ['errors' => $e->errors()]);
            return response()->json([
                "success" => false,
                "message" => "Datos de validación incorrectos",
                "data" => null,
                "errors" => $e->errors(),
                "meta" => [
                    "apiVersion" => "1.0",
                    "timestamp" => now()->toIso8601String()
                ]
            ], 422);
        } catch (\Exception $e) {
            Log::info('quickSearch: Excepción general', ['message' => $e->getMessage()]);
            return response()->json([
                "success" => false,
                "message" => "Error en la búsqueda rápida",
                "data" => null,
                "errors" => $e->getMessage(),
                "meta" => [
                    "apiVersion" => "1.0",
                    "timestamp" => now()->toIso8601String()
                ]
            ], 500);
        }
    }
}
