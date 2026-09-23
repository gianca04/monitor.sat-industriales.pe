<?php

namespace App\Actions;

use App\Models\Requirement;
use App\Models\RequirementList;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateRequirementAction
{
    /**
     * Crea un requerimiento principal y asocia sus ítems de forma atómica en una transacción.
     *
     * @param  array{sub_client_id: int, activity_name?: string|null}  $requirementData  Datos del requerimiento
     * @param  array<int, array{item_id?: int, id?: int, quantity: float|int}>  $items  Lista de materiales a solicitar
     * @param  User|int|null  $user  Usuario solicitante (Web o API)
     * @return Requirement Requerimiento creado con relaciones y conteos cargados
     */
    public function execute(array $requirementData, array $items = [], User|int|null $user = null): Requirement
    {
        $userId = match (true) {
            $user instanceof User => $user->id,
            is_numeric($user) => (int) $user,
            default => auth()->id() ?? auth('sanctum')->id(),
        };

        return DB::transaction(function () use ($requirementData, $items, $userId) {
            // 1. Crear el requerimiento principal y almacenar su ID
            $requirement = Requirement::create([
                'sub_client_id' => $requirementData['sub_client_id'],
                'activity_name' => $requirementData['activity_name'] ?? null,
                'created_by' => $userId,
            ]);

            // 2. Recorrer la lista de ítems y asociar cada uno al requerimiento creado
            foreach ($items as $itemData) {
                $itemId = $itemData['item_id'] ?? $itemData['id'] ?? null;
                $quantity = (float) ($itemData['quantity'] ?? 1);

                if ($itemId && $quantity > 0) {
                    RequirementList::create([
                        'requirement_id' => $requirement->id,
                        'item_id' => (int) $itemId,
                        'quantity' => $quantity,
                    ]);
                }
            }

            // 3. Retornar el requerimiento con sus relaciones y conteos necesarios
            return $requirement->load([
                'subClient.client',
                'creator',
                'requirementLists.item.unit',
            ])->loadCount('requirementLists');
        });
    }
}
