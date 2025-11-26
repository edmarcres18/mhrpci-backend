<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsumableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'consumable_name' => ['required', 'string', 'max:255'],
            'consumable_description' => ['nullable', 'string'],
            'consumable_brand' => ['nullable', 'string', 'max:255'],
            'current_quantity' => ['required', 'integer', 'min:0'],
            'threshold_limit' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
        ];
    }
}

