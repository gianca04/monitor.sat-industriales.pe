<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
// use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDataController extends Controller
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
            'position_id' => 'nullable|integer|exists:positions,id',
            'active' => 'nullable|boolean',
            'created_after' => 'nullable|date',
            'sort_by' => 'nullable|in:id,document_number,first_name,last_name,date_contract,date_birth,position_id,created_at,updated_at',
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

        $query = Employee::with('position');

        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('document_number', 'like', $term)
                    ->orWhere('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('address', 'like', $term);
            });
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', $request->position_id);
        }

        if ($request->filled('active')) {
            $query->where('active', (bool) $request->active);
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

            $data = $items->map(fn($employee) => $this->transformEmployee($employee))->values();

            $nextCursor = null;
            if ($data->isNotEmpty()) {
                $last = $items->last();
                $nextCursor = $cursorField === 'id'
                    ? $last->id
                    : ($last->created_at?->toIso8601String() ?? (string) $last->created_at);
            }

            return response()->json([
                'success' => true,
                'message' => 'Employees retrieved (cursor)',
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

        $data = $paginator->getCollection()->map(fn($e) => $this->transformEmployee($e));

        return response()->json([
            'success' => true,
            'message' => 'Employees retrieved',
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

    /* ===============================================================
     *  MÉTODO BATCH – COMENTADO (NO USAR)
     * =============================================================== */

    /*
    public function batch(Request $request)
    {
        // ... MÉTODO COMPLETO COMENTADO ...
    }
    */

    /* ===============================================================
     *  MÉTODO EXPORT NDJSON – COMENTADO (NO USAR)
     * =============================================================== */

    /*
    public function export(Request $request)
    {
        // ... MÉTODO COMPLETO COMENTADO ...
    }
    */

    /**
     * Transformación uniforme del empleado.
     */
    private function transformEmployee(Employee $e)
    {
        return [
            'id' => $e->id,
            'documentType' => $e->document_type,
            'documentNumber' => $e->document_number,
            'firstName' => $e->first_name,
            'lastName' => $e->last_name,
            'address' => $e->address,
            'dateContract' => $e->date_contract?->format('Y-m-d'),
            'dateBirth' => $e->date_birth?->format('Y-m-d'),
            'sex' => $e->sex,
            'positionId' => $e->position_id,
            'active' => (bool) $e->active,
            'createdAt' => $e->created_at?->toIso8601String(),
            'updatedAt' => $e->updated_at?->toIso8601String(),
            'position' => $e->position ? [
                'id' => $e->position->id,
                'name' => $e->position->name,
            ] : null,
        ];
    }
}
