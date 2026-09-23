<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequirementListRequest;
use App\Http\Requests\UpdateRequirementListRequest;
use App\Http\Resources\RequirementListResource;
use App\Models\Requirement;
use App\Models\RequirementList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequirementListController extends Controller
{
    /**
     * Display a listing of items for the specified requirement with search, category filtering and pagination.
     */
    public function index(Request $request, Requirement $requirement): AnonymousResourceCollection
    {
        $query = $requirement->requirementLists()->with(['item.unit', 'item.subcategory.category']);

        if ($request->filled('search')) {
            $searchTerm = '%'.$request->search.'%';
            $query->whereHas('item', function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('sku', 'like', $searchTerm);
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('item.subcategory', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('subcategory_id')) {
            $query->whereHas('item', function ($q) use ($request) {
                $q->where('subcategory_id', $request->subcategory_id);
            });
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        if ($sortBy === 'name') {
            $query->join('items', 'requirement_lists.item_id', '=', 'items.id')
                ->orderBy('items.name', $sortOrder)
                ->select('requirement_lists.*');
        } elseif (in_array($sortBy, ['id', 'quantity', 'created_at'])) {
            $query->orderBy('requirement_lists.'.$sortBy, $sortOrder);
        } else {
            $query->orderBy('requirement_lists.created_at', 'desc');
        }

        $perPage = (int) $request->input('per_page', 15);

        return RequirementListResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created item in the requirement list.
     */
    public function store(StoreRequirementListRequest $request, Requirement $requirement): JsonResponse
    {
        $data = $request->validated();
        $data['requirement_id'] = $requirement->id;

        // Si el ítem ya existe en la lista del requerimiento, sumamos la cantidad
        $existing = RequirementList::where('requirement_id', $requirement->id)
            ->where('item_id', $data['item_id'])
            ->first();

        if ($existing) {
            $existing->increment('quantity', $data['quantity']);
            $existing->load('item.unit');

            return (new RequirementListResource($existing))
                ->response()
                ->setStatusCode(200);
        }

        $requirementList = RequirementList::create($data);
        $requirementList->load('item.unit');

        return (new RequirementListResource($requirementList))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified requirement list item.
     */
    public function show(Requirement $requirement, RequirementList $item): RequirementListResource
    {
        $item->load('item.unit');

        return new RequirementListResource($item);
    }

    /**
     * Update the specified requirement list item.
     */
    public function update(UpdateRequirementListRequest $request, Requirement $requirement, RequirementList $item): RequirementListResource
    {
        $item->update($request->validated());
        $item->load('item.unit');

        return new RequirementListResource($item);
    }

    /**
     * Remove the specified item from the requirement list.
     */
    public function destroy(Requirement $requirement, RequirementList $item): JsonResponse
    {
        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ítem eliminado del requerimiento exitosamente.',
        ], 200);
    }

    /**
     * Remove all items from the requirement list.
     */
    public function clear(Requirement $requirement): JsonResponse
    {
        $count = $requirement->requirementLists()->delete();

        return response()->json([
            'success' => true,
            'message' => "Se eliminaron {$count} materiales del requerimiento.",
            'deleted_count' => $count,
        ], 200);
    }
}
