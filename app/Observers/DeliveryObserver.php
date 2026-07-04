<?php

namespace App\Observers;

use App\Models\Delivery;
use App\Enums\DeliveryStatus;

class DeliveryObserver
{
    /**
     * Handle the Delivery "saving" event.
     */
    public function saving(Delivery $delivery): void
    {
        if ($delivery->status === DeliveryStatus::DELIVERED) {
            if (empty($delivery->delivery_date)) {
                $delivery->delivery_date = now();
            }
            if (empty($delivery->delivered_by)) {
                $delivery->delivered_by = auth()->user()?->employee_id;
            }
        }
    }
}
