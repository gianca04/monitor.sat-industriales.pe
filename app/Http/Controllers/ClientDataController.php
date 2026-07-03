<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClientDataController extends Controller
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
            'person_type' => 'nullable|string|in:natural,juridical',
            'created_after' => 'nullable|date',
            'sort_by' => 'nullable|in:id,document_number,business_name,description,address,contact_email,created_at,updated_at',
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

        $query = Client::with('projects', 'subClients');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('document_number', 'like', $term)
                    ->orWhere('business_name', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhere('address', 'like', $term)
                    ->orWhere('contact_email', 'like', $term);
            });
        }

        if ($request->filled('person_type')) {
            $query->where('person_type', $request->person_type);
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

            $data = $items->map(fn($client) => $this->transformClient($client))->values();

            $nextCursor = null;
            if ($data->isNotEmpty()) {
                $last = $items->last();
                $nextCursor = $cursorField === 'id'
                    ? $last->id
                    : ($last->created_at?->toIso8601String() ?? (string) $last->created_at);
            }

            return response()->json([
                'success' => true,
                'message' => 'Clients retrieved (cursor)',
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

        $data = $paginator->getCollection()->map(fn($c) => $this->transformClient($c));

        return response()->json([
            'success' => true,
            'message' => 'Clients retrieved',
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
     * Transformación uniforme del cliente.
     */
    private function transformClient(Client $c)
    {
        return [
            'id' => $c->id,
            'documentType' => $c->document_type,
            'documentNumber' => $c->document_number,
            'personType' => $c->person_type,
            'businessName' => $c->business_name,
            'description' => $c->description,
            'address' => $c->address,
            'contactPhone' => $c->contact_phone,
            'contactEmail' => $c->contact_email,
            'logo' => $c->logo,
            'createdAt' => $c->created_at?->toIso8601String(),
            'updatedAt' => $c->updated_at?->toIso8601String(),
            'projects' => $c->projects->map(fn($p) => ['id' => $p->id, 'name' => $p->name])->toArray(),
            'subClients' => $c->subClients->map(fn($sc) => ['id' => $sc->id, 'name' => $sc->name])->toArray(),
        ];
    }
}