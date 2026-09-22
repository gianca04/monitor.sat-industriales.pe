<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRequirementListRequest;
use App\Http\Requests\UpdateRequirementListRequest;
use App\Http\Resources\RequirementListResource;
use App\Models\Requirement;
use App\Models\RequirementList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RequirementListController extends Controller
{
    /**
     * Display a listing of items for the specified requirement.
     */
    public function index(Requirement $requirement): AnonymousResourceCollection
    {
        $items = $requirement->requirementLists()->with('item.unit')->get();

        return RequirementListResource::collection($items);
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
}
