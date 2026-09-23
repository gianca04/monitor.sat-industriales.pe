<?php

namespace App\Actions;

use App\Models\Requirement;
use App\Models\RequirementList;
use Illuminate\Support\Facades\DB;

class UpdateRequirementAction
{
    /**
     * Actualiza un requerimiento principal y sincroniza sus materiales atómicamente.
     *
     * @param  Requirement  $requirement  Modelo de requerimiento existente
     * @param  array{sub_client_id?: int, activity_name?: string|null}  $requirementData  Datos del requerimiento
     * @param  array<int, array{item_id?: int, id?: int, quantity: float|int}>|null  $items  Lista de materiales actualizada
     * @return Requirement Requerimiento actualizado con relaciones y conteos
     */
    public function execute(Requirement $requirement, array $requirementData, ?array $items = null): Requirement
    {
        return DB::transaction(function () use ($requirement, $requirementData, $items) {
            // 1. Actualizar los datos del requerimiento principal
            if (! empty($requirementData)) {
                $requirement->update($requirementData);
            }

            // 2. Si se proporciona la lista de materiales, sincronizar atómicamente
            if (is_array($items)) {
                $processedItemIds = [];

                foreach ($items as $itemData) {
                    $itemId = $itemData['item_id'] ?? $itemData['id'] ?? null;
                    $quantity = (float) ($itemData['quantity'] ?? 1);

                    if ($itemId && $quantity > 0) {
                        $processedItemIds[] = (int) $itemId;

                        RequirementList::updateOrCreate(
                            [
                                'requirement_id' => $requirement->id,
                                'item_id' => (int) $itemId,
                            ],
                            [
                                'quantity' => $quantity,
                            ]
                        );
                    }
                }

                // Eliminar ítems que ya no se encuentran en la lista actualizada
                if (! empty($processedItemIds)) {
                    $requirement->requirementLists()
                        ->whereNotIn('item_id', $processedItemIds)
                        ->delete();
                } else {
                    // Si se envió un array de ítems vacío, vaciar la lista
                    $requirement->requirementLists()->delete();
                }
            }

            // 3. Recargar relaciones y conteo
            return $requirement->load([
                'subClient.client',
                'creator',
                'requirementLists.item.unit',
                'requirementLists.item.subcategory.category',
            ])->loadCount('requirementLists');
        });
    }
}
