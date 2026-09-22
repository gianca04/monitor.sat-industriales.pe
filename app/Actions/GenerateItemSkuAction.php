<?php

namespace App\Actions;

use App\Models\Item;
use App\Models\Subcategory;

class GenerateItemSkuAction
{
    /**
     * Genera un SKU único para un Item basado en su subcategoría o prefijo.
     * Ejemplo de formato: SUB-00001
     */
    public function execute(int|Subcategory $subcategory, string $prefix = 'ITM'): string
    {
        if (is_int($subcategory)) {
            $subcategory = Subcategory::find($subcategory);
        }

        if ($subcategory && ! empty($subcategory->name)) {
            // Tomamos las primeras 3 letras de la subcategoría limpia (sin espacios ni acentos)
            $cleanName = preg_replace('/[^A-Za-z0-9]/', '', $subcategory->name);
            $prefix = strtoupper(substr($cleanName, 0, 3));
        }

        // Buscamos el último ID correlativo o conteo existente con ese prefijo
        $lastId = Item::max('id') ?? 0;
        $nextNumber = $lastId + 1;

        $sku = sprintf('%s-%05d', $prefix, $nextNumber);

        // Verificación de unicidad por seguridad
        while (Item::where('sku', $sku)->exists()) {
            $nextNumber++;
            $sku = sprintf('%s-%05d', $prefix, $nextNumber);
        }

        return $sku;
    }
}
