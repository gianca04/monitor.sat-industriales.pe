<?php

namespace App\Http\Controllers;

use App\Actions\GenerateItemSkuAction;
use App\Actions\UploadItemPhotoAction;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Http\Resources\ItemResource;
use App\Jobs\UploadItemPhotoJob;
use App\Models\Item;
use App\Services\ItemPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ItemController extends Controller
{
    public function __construct(
        protected UploadItemPhotoAction $uploadItemPhotoAction,
        protected ItemPhotoService $itemPhotoService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Item::with(['subcategory.category', 'unit', 'creator']);

        if ($request->filled('search')) {
            $searchTerm = '%'.$request->search.'%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                    ->orWhere('sku', 'like', $searchTerm);
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->unit_id);
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');

        if (in_array($sortBy, ['id', 'sku', 'name', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        $perPage = (int) $request->input('per_page', 15);

        return ItemResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreItemRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        if (empty($data['sku'])) {
            $subcategoryId = $data['subcategory_id'] ?? 0;
            $data['sku'] = app(GenerateItemSkuAction::class)->execute($subcategoryId);
        }

        unset($data['photo']);

        $item = Item::create($data);

        if ($request->hasFile('photo')) {
            $this->uploadItemPhotoAction->execute($item, $request->file('photo'));
        }

        $item->load(['subcategory', 'unit', 'creator']);

        return (new ItemResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item): ItemResource
    {
        $item->load(['subcategory', 'unit', 'creator']);

        return new ItemResource($item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateItemRequest $request, Item $item): ItemResource
    {
        $data = $request->validated();
        unset($data['photo']);

        $item->update($data);

        if ($request->hasFile('photo')) {
            $this->uploadItemPhotoAction->execute($item, $request->file('photo'));
        }

        $item->load(['subcategory', 'unit', 'creator']);

        return new ItemResource($item);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item): JsonResponse
    {
        if ($item->requirementLists()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el ítem porque está asociado a uno o más requerimientos.',
            ], 422);
        }

        if ($item->photo) {
            $this->itemPhotoService->delete($item->photo);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ítem eliminado exitosamente.',
        ], 200);
    }

    /**
     * Poner en cola la subida de una fotografía para un ítem.
     */
    public function queuePhotoUpload(Request $request, ?Item $item = null): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'item_id' => [$item ? 'nullable' : 'required', 'exists:items,id'],
        ], [
            'photo.required' => 'Debe adjuntar una imagen.',
            'photo.image' => 'El archivo adjunto debe ser una imagen válida.',
            'photo.mimes' => 'La imagen debe ser de formato jpeg, png, jpg o webp.',
            'photo.max' => 'La imagen no debe superar los 10MB.',
            'item_id.required' => 'El ID del ítem es obligatorio.',
            'item_id.exists' => 'El ítem especificado no existe.',
        ]);

        $targetItem = $item ?? Item::findOrFail($request->input('item_id'));

        // Guardar temporalmente en el disco local para que el worker de colas lo procese
        $tempPath = $request->file('photo')->store('temp/item-photos', 'local');

        UploadItemPhotoJob::dispatch($targetItem, $tempPath);

        return response()->json([
            'success' => true,
            'message' => 'La fotografía del ítem ha sido puesta en cola para su procesamiento.',
            'data' => [
                'item_id' => $targetItem->id,
                'sku' => $targetItem->sku,
                'status' => 'queued',
            ],
        ], 202);
    }
}
