<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsumableUsageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'consumable_id' => $this->consumable_id,
            'quantity_used' => (int) $this->quantity_used,
            'purpose' => $this->purpose,
            'used_by' => $this->used_by,
            'date_used' => optional($this->date_used)->format('Y-m-d'),
            'notes' => $this->notes,
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'consumable' => [
                'id' => optional($this->consumable)->id,
                'name' => optional($this->consumable)->consumable_name,
                'brand' => optional($this->consumable)->consumable_brand,
                'unit' => optional($this->consumable)->unit,
            ],
        ];
    }
}
