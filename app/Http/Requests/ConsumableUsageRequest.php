<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsumableUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity_used' => ['required', 'integer', 'min:1'],
            'purpose' => ['required', 'string', 'max:255'],
            'used_by' => ['required', 'string', 'max:255'],
            'date_used' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
