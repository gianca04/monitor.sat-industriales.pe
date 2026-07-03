<?php

namespace App\Http\Controllers;

use App\Models\SubClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubClientDataController extends Controller
{
    /**
     * Método PRINCIPAL para sincronización vía cursor.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'per_page' => 'nullable|integer|min:1|max:1000',
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string|max:255',
            'client_id' => 'nullable|integer|exists:clients,id',
            'created_after' => 'nullable|date',
            'sort_by' => 'nullable|in:id,name,description,address,created_at,updated_at',
            'sort_order' => 'nullable|in:asc,desc',

            // Cursor based pagination
            'cursor' => 'nullable',
            'cursor_field' => 'nullable|in:id,created_at',
            'direction' => 'nullable|in:after,before',
            'limit' => 'nullable|integer|min:1|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = SubClient::with('client', 'quotes', 'requests', 'contactData');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('address', 'like', $term);
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('created_after')) {
            $query->where('created_at', '>', $request->created_after);
        }

        /** -----------------------------
         *  CURSOR BASED PAGINATION (ACTIVO)
         * ----------------------------- */
        if ($request->filled('cursor')) {
            $cursorField = $request->cursor_field ?? 'id';
            $direction = $request->direction ?? 'after';
            $limit = (int) ($request->limit ?? ($request->per_page ?? 100));

            $operator = $direction === 'after' ? '>' : '<';
            $orderDir = $direction === 'after' ? 'asc' : 'desc';

            if ($cursorField === 'id') {
                $query->where('id', $operator, $request->cursor);
                $query->orderBy('id', $orderDir);
            } else {
                $query->where('created_at', $operator, $request->cursor);
                $query->orderBy('created_at', $orderDir)->orderBy('id', $orderDir);
            }

            $items = $query->take($limit + 1)->get();
            $hasMore = $items->count() > $limit;

            if ($hasMore) {
                $items = $items->slice(0, $limit);
            }

            if ($direction === 'before') {
                $items = $items->reverse()->values();
            }

            $data = $items->map(fn($subClient) => $this->transformSubClient($subClient))->values();

            $nextCursor = null;
            if ($data->isNotEmpty()) {
                $last = $items->last();
                $nextCursor = $cursorField === 'id'
                    ? $last->id
                    : ($last->created_at?->toIso8601String() ?? (string) $last->created_at);
            }

            return response()->json([
                'success' => true,
                'message' => 'SubClients retrieved (cursor)',
                'data' => $data,
                'cursor' => [
                    'nextCursor' => $hasMore ? $nextCursor : null,
                    'cursorField' => $cursorField,
                    'direction' => $direction,
                    'limit' => $limit,
                ],
            ], 200);
        }

        /** -----------------------------------
         *  PAGINACIÓN OFFSET (LEGACY – mantenimiento)
         * ----------------------------------- */
        $sortBy = $request->sort_by ?? 'id';
        $sortOrder = $request->sort_order ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->per_page ?? 100;
        $paginator = $query->paginate($perPage);

        $data = $paginator->getCollection()->map(fn($sc) => $this->transformSubClient($sc));

        return response()->json([
            'success' => true,
            'message' => 'SubClients retrieved',
            'data' => $data,
            'pagination' => [
                'total' => $paginator->total(),
                'perPage' => $paginator->perPage(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'hasMorePages' => $paginator->hasMorePages(),
            ],
        ], 200);
    }

    /**
     * Transformación uniforme del subcliente.
     */
    private function transformSubClient(SubClient $sc)
    {
        return [
            'id' => $sc->id,
            'clientId' => $sc->client_id,
            'name' => $sc->name,
            'description' => $sc->description,
            'location' => $sc->location,
            'latitude' => $sc->latitude,
            'longitude' => $sc->longitude,
            'address' => $sc->address,
            'coordinates' => $sc->coordinates,
            'createdAt' => $sc->created_at?->toIso8601String(),
            'updatedAt' => $sc->updated_at?->toIso8601String(),
            'client' => $sc->client ? [
                'id' => $sc->client->id,
                'businessName' => $sc->client->business_name,
            ] : null,
            'quotes' => $sc->quotes->map(fn($q) => ['id' => $q->id, 'title' => $q->title ?? ''])->toArray(),
            'requests' => $sc->requests->map(fn($r) => ['id' => $r->id, 'title' => $r->title ?? ''])->toArray(),
            'contactData' => $sc->contactData->map(fn($cd) => ['id' => $cd->id, 'name' => $cd->name])->toArray(),
        ];
    }
}