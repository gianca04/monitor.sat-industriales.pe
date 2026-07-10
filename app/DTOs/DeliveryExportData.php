<?php

namespace App\DTOs;

use App\Models\Delivery;

class DeliveryExportData
{
    /**
     * @param string $employeeName Nombre y apellidos del trabajador
     * @param string $employeeDni DNI del trabajador
     * @param string $employeeArea Área del trabajador
     * @param DeliveryExportItem[] $items Lista de ítems entregados
     * @param string $documentCode Códigos de los detalles de entrega
     * @param string $exportDate Fecha de la exportación
     * @param string $creatorFirstName Nombre del creador
     * @param string $creatorLastName Apellido del creador
     * @param string $creatorPosition Cargo del creador
     * @param string $creatorFullDate Fecha completa de creación
     */
    public function __construct(
        public string $employeeName,
        public string $employeeDni,
        public string $employeeArea,
        public array $items,
        public string $documentCode,
        public string $exportDate,
        public string $creatorFirstName,
        public string $creatorLastName,
        public string $creatorPosition,
        public string $creatorFullDate
    ) {}

    /**
     * Crea una instancia del DTO a partir del modelo Delivery
     *
     * @param Delivery $delivery
     * @return self
     */
    public static function fromModel(Delivery $delivery): self
    {
        $delivery->loadMissing(['details.eppVariant.epp.subcategories.category']);
        
        list($employeeName, $employeeDni, $employeeArea) = self::determineHeaderInfo($delivery->details, $delivery);
        list($creatorFirstName, $creatorLastName, $creatorPosition, $creatorFullDate) = self::determineCreatorInfo();

        $documentCode = $delivery->details->pluck('id')->implode(', ');
        $exportDate = now()->format('d/m/Y');

        $items = [];

        foreach ($delivery->details as $detail) {
            $sku = $detail->eppVariant?->sku ?? 'N/A';
            
            // Obtener el tipo de EPP: Categoría + Subcategoría
            $epp = $detail->eppVariant?->epp;
            $subcategoryNames = [];
            $categoryName = '';
            
            if ($epp) {
                foreach ($epp->subcategories as $sub) {
                    $subcategoryNames[] = $sub->name;
                    if ($sub->category) {
                        $categoryName = $sub->category->name;
                    }
                }
            }

            // Construir cadena: "Categoría - Subcategoría"
            $typeString = $categoryName;
            if (!empty($subcategoryNames)) {
                $typeString .= ($typeString ? ' - ' : '') . implode(', ', $subcategoryNames);
            }
            if (empty($typeString) && $epp) {
                $typeString = $epp->name;
            }

            $items[] = new DeliveryExportItem(
                sku: $sku,
                type: $typeString ?: 'N/A',
                quantity: $detail->quantity,
                deliveredAt: $detail->delivered_at,
                notes: $detail->notes
            );
        }

        return new self(
            employeeName: $employeeName,
            employeeDni: $employeeDni,
            employeeArea: $employeeArea,
            items: $items,
            documentCode: $documentCode,
            exportDate: $exportDate,
            creatorFirstName: $creatorFirstName,
            creatorLastName: $creatorLastName,
            creatorPosition: $creatorPosition,
            creatorFullDate: $creatorFullDate
        );
    }

    /**
     * Crea una instancia del DTO a partir de una colección filtrada/seleccionada de detalles
     *
     * @param \Illuminate\Support\Collection $details
     * @param Delivery $delivery
     * @return self
     */
    public static function fromDetailsCollection($details, Delivery $delivery): self
    {
        $details->loadMissing(['eppVariant.epp.subcategories.category']);

        list($employeeName, $employeeDni, $employeeArea) = self::determineHeaderInfo($details, $delivery);
        list($creatorFirstName, $creatorLastName, $creatorPosition, $creatorFullDate) = self::determineCreatorInfo();

        $documentCode = $details->pluck('id')->implode(', ');
        $exportDate = now()->format('d/m/Y');

        $items = [];

        foreach ($details as $detail) {
            $sku = $detail->eppVariant?->sku ?? 'N/A';
            
            $epp = $detail->eppVariant?->epp;
            $subcategoryNames = [];
            $categoryName = '';
            
            if ($epp) {
                foreach ($epp->subcategories as $sub) {
                    $subcategoryNames[] = $sub->name;
                    if ($sub->category) {
                        $categoryName = $sub->category->name;
                    }
                }
            }

            $typeString = $categoryName;
            if (!empty($subcategoryNames)) {
                $typeString .= ($typeString ? ' - ' : '') . implode(', ', $subcategoryNames);
            }
            if (empty($typeString) && $epp) {
                $typeString = $epp->name;
            }

            $items[] = new DeliveryExportItem(
                sku: $sku,
                type: $typeString ?: 'N/A',
                quantity: $detail->quantity,
                deliveredAt: $detail->delivered_at,
                notes: $detail->notes
            );
        }

        return new self(
            employeeName: $employeeName,
            employeeDni: $employeeDni,
            employeeArea: $employeeArea,
            items: $items,
            documentCode: $documentCode,
            exportDate: $exportDate,
            creatorFirstName: $creatorFirstName,
            creatorLastName: $creatorLastName,
            creatorPosition: $creatorPosition,
            creatorFullDate: $creatorFullDate
        );
    }

    /**
     * Determina los valores del encabezado validando si todos los detalles pertenecen al mismo empleado o tienda
     *
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $details
     * @param Delivery $delivery
     * @return array
     */
    private static function determineHeaderInfo($details, Delivery $delivery): array
    {
        $details->loadMissing(['employee', 'subClient']);

        // Verificar si todos los detalles tienen el mismo employee_id
        $employeeIds = $details->pluck('employee_id')->filter()->unique();
        
        $employeeName = 'N/A';
        $employeeDni = 'N/A';
        
        if ($employeeIds->count() === 1) {
            $firstDetail = $details->first(fn($d) => !empty($d->employee_id));
            $employee = $firstDetail?->employee;
            if ($employee) {
                $employeeName = "{$employee->first_name} {$employee->last_name}";
                $employeeDni = $employee->document_number ?: 'N/A';
            }
        } else {
            // Fallback al empleado de la entrega principal
            $employee = $delivery->employee;
            $employeeName = $employee ? "{$employee->first_name} {$employee->last_name}" : 'VARIOS';
            $employeeDni = $employee ? $employee->document_number : 'N/A';
        }

        // Verificar si todos los detalles tienen el mismo sub_client_id
        $subClientIds = $details->pluck('sub_client_id')->filter()->unique();
        $employeeArea = 'N/A';
        
        if ($subClientIds->count() === 1) {
            $firstDetail = $details->first(fn($d) => !empty($d->sub_client_id));
            $employeeArea = $firstDetail?->subClient?->name ?: 'N/A';
        } else {
            // Fallback a la tienda de la entrega principal
            $employeeArea = $delivery->subClient ? $delivery->subClient->name : 'VARIOS';
        }

        return [$employeeName, $employeeDni, $employeeArea];
    }

    /**
     * Determina los valores del creador del reporte a partir del usuario autenticado
     *
     * @return array
     */
    private static function determineCreatorInfo(): array
    {
        $creator = auth()->user();
        if ($creator) {
            $creator->loadMissing(['employee.position']);
            $employee = $creator->employee;
            
            $firstName = $employee?->first_name ?? $creator->name;
            $lastName = $employee?->last_name ?? '';
            $position = $employee?->position?->name ?? 'N/A';
        } else {
            $firstName = 'N/A';
            $lastName = '';
            $position = 'N/A';
        }

        $fullDate = now()->translatedFormat('d \d\e F \d\e Y');

        return [$firstName, $lastName, $position, $fullDate];
    }
}
