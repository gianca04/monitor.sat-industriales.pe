<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        try {
            $projects = Project::with(['quote.client', 'quote.sub_client'])
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->when($request->query('name'), function ($q, $name) {
                    $q->where('name', 'like', "%$name%");
                })
                ->when($request->query('client_name'), function ($q, $clientName) {
                    $q->whereHas('quote.client', function ($subQ) use ($clientName) {
                        $subQ->where('business_name', 'like', "%$clientName%");
                    });
                })
                ->when($request->query('subclient_name'), function ($q, $subclientName) {
                    $q->whereHas('quote.sub_client', function ($subQ) use ($subclientName) {
                        $subQ->where('name', 'like', "%$subclientName%");
                    });
                })
                ->orderBy('name')
                ->get();

            $data = $projects->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'start_date' => $project->start_date,
                    'end_date' => $project->end_date,
                    'tdr' => $project->quote ? $project->quote->TDR : null,
                    'quote_id' => $project->quote ? $project->quote->id : null,
                    'client' => $project->quote && $project->quote->client ? [
                        'id' => $project->quote->client->id,
                        'business_name' => $project->quote->client->business_name,
                    ] : null,
                    'sub_client' => $project->quote && $project->quote->sub_client ? [
                        'id' => $project->quote->sub_client->id,
                        'name' => $project->quote->sub_client->name,
                    ] : null,
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
}
