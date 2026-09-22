<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubcategoryRequest;
use App\Http\Resources\SubcategoryResource;
use App\Models\Subcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of subcategories with search, category filtering and pagination.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Subcategory::with('category');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('search')) {
            $term = '%'.$request->search.'%';
            $query->where('name', 'like', $term);
        }

        $sortBy = $request->input('sort_by', 'name');
        $sortOrder = $request->input('sort_order', 'asc');

        if (in_array($sortBy, ['id', 'name', 'category_id', 'created_at'])) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('name', 'asc');
        }

        $perPage = (int) $request->input('per_page', 15);

        return SubcategoryResource::collection($query->paginate($perPage));
    }

    /**
     * Store a newly created subcategory in storage.
     */
    public function store(StoreSubcategoryRequest $request): JsonResponse
    {
        $subcategory = Subcategory::create($request->validated());
        $subcategory->load('category');

        return (new SubcategoryResource($subcategory))
            ->response()
            ->setStatusCode(201);
    }
}
