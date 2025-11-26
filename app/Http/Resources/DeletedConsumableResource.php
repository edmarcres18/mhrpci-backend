<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeletedConsumableResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'consumable_id' => $this->consumable_id,
            'consumable_name' => $this->consumable_name,
            'consumable_brand' => $this->consumable_brand,
            'consumable_description' => $this->consumable_description,
            'current_quantity' => (int) $this->current_quantity,
            'threshold_limit' => (int) $this->threshold_limit,
            'unit' => $this->unit,
            'deleted_at' => optional($this->deleted_at)->toDateTimeString(),
            'restore_status' => (bool) $this->restore_status,
            'deleted_by' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => (string) $this->user->name,
                ];
            }),
        ];
    }
}

