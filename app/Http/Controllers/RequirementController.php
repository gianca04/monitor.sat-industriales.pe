<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequirementRequest;
use App\Http\Requests\UpdateRequirementRequest;
use App\Http\Resources\RequirementResource;
use App\Models\Requirement;
use App\Models\RequirementList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class RequirementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Requirement::with(['subClient', 'creator', 'requirementLists.item.unit'])
            ->withCount('requirementLists');

        if ($request->filled('search')) {
            $searchTerm = '%'.$request->search.'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('activity_name', 'like', $searchTerm)
                    ->orWhereHas('subClient', function ($sq) use ($searchTerm) {
                        $sq->where('name', 'like', $searchTerm);
                    });
            });
        }

        if ($request->filled('sub_client_id')) {
            $query->where('sub_client_id', $request->sub_client_id);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if (in_array($sortBy, ['id', 'activity_name', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = (int) $request->input('per_page', 15);

        return RequirementResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequirementRequest $request): JsonResponse
    {
        $requirement = DB::transaction(function () use ($request) {
            $requirement = Requirement::create([
                'sub_client_id' => $request->sub_client_id,
                'activity_name' => $request->activity_name,
                'created_by' => auth()->id(),
            ]);

            if ($request->filled('items') && is_array($request->items)) {
                foreach ($request->items as $itemData) {
                    RequirementList::create([
                        'requirement_id' => $requirement->id,
                        'item_id' => $itemData['item_id'],
                        'quantity' => $itemData['quantity'],
                    ]);
                }
            }

            return $requirement;
        });

        $requirement->load(['subClient', 'creator', 'requirementLists.item.unit'])
            ->loadCount('requirementLists');

        return (new RequirementResource($requirement))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Requirement $requirement): RequirementResource
    {
        $requirement->load(['subClient', 'creator', 'requirementLists.item.unit'])
            ->loadCount('requirementLists');

        return new RequirementResource($requirement);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequirementRequest $request, Requirement $requirement): RequirementResource
    {
        $requirement->update($request->validated());
        $requirement->load(['subClient', 'creator', 'requirementLists.item.unit'])
            ->loadCount('requirementLists');

        return new RequirementResource($requirement);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Requirement $requirement): JsonResponse
    {
        $requirement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Requerimiento eliminado exitosamente.',
        ], 200);
    }
}
