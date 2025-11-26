<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsumableResource extends JsonResource
{
    public function toArray($request): array
    {
        $status = 'healthy';
        if ((int) $this->current_quantity === 0) {
            $status = 'critical';
        } elseif ((int) $this->current_quantity <= (int) $this->threshold_limit) {
            $status = 'low';
        }

        return [
            'id' => $this->id,
            'consumable_name' => $this->consumable_name,
            'consumable_description' => $this->consumable_description,
            'consumable_brand' => $this->consumable_brand,
            'current_quantity' => (int) $this->current_quantity,
            'threshold_limit' => (int) $this->threshold_limit,
            'unit' => $this->unit,
            'stock_status' => $status,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'deleted_at' => optional($this->deleted_at)->toDateTimeString(),
        ];
    }
}
