<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConsumableLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'action' => (string) $this->action,
            'changes' => $this->changes,
            'consumable' => $this->whenLoaded('consumable', function () {
                return [
                    'id' => $this->consumable->id,
                    'name' => (string) $this->consumable->consumable_name,
                ];
            }),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => (string) $this->user->name,
                ];
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}

