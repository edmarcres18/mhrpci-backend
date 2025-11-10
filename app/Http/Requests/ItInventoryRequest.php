<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItInventoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'inventory_name' => ['required', 'string', 'max:255'],
            'descriptions' => ['nullable', 'string', 'max:5000'],
            'accountable_by_name' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'inventory_name.required' => 'Inventory name is required.',
            'inventory_name.max' => 'Inventory name must not exceed 255 characters.',
            'descriptions.max' => 'Descriptions must not exceed 5000 characters.',
            'accountable_by_name.max' => 'Accountable by name must not exceed 255 characters.',
            'remarks.max' => 'Remarks must not exceed 2000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inventory_name' => 'inventory name',
            'descriptions' => 'descriptions',
            'accountable_by_name' => 'accountable by name',
            'remarks' => 'remarks',
        ];
    }
}