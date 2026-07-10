<?php

namespace App\DTOs;

class DeliveryExportItem
{
    public function __construct(
        public string $sku,
        public string $type,
        public int $quantity,
        public ?\Carbon\Carbon $deliveredAt,
        public ?string $notes
    ) {}
}
