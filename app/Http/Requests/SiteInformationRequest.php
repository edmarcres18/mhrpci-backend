<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SiteInformationRequest extends FormRequest
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
            'email_address' => [
                'nullable',
                'email:rfc,dns',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'tel_no' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
            ],
            'phone_no' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'telegram' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^@?[a-zA-Z0-9_]{5,32}$|^https?:\/\/(t\.me|telegram\.me)\/[a-zA-Z0-9_]{5,32}$/',
            ],
            'facebook' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(https?:\/\/)?(www\.)?(facebook|fb)\.com\/[a-zA-Z0-9.]+\/?$|^[a-zA-Z0-9.]+$/',
            ],
            'viber' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
            ],
            'whatsapp' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
            ],
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
            'email_address.email' => 'Please provide a valid email address.',
            'email_address.regex' => 'Email format is invalid.',

            'tel_no.regex' => 'Please enter a valid telephone number (e.g., +1234567890 or 123-456-7890).',

            'phone_no.regex' => 'Please enter a valid phone number (e.g., +1234567890 or 123-456-7890).',

            'address.max' => 'Address must not exceed 255 characters.',

            'telegram.regex' => 'Please enter a valid Telegram username (e.g., @username or https://t.me/username).',

            'facebook.regex' => 'Please enter a valid Facebook profile (e.g., facebook.com/username or just username).',

            'viber.regex' => 'Please enter a valid Viber number (e.g., +1234567890 or 123-456-7890).',

            'whatsapp.regex' => 'Please enter a valid WhatsApp number (e.g., +1234567890 or 123-456-7890).',
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
            'email_address' => 'email address',
            'tel_no' => 'telephone number',
            'phone_no' => 'phone number',
            'address' => 'address',
            'telegram' => 'Telegram',
            'facebook' => 'Facebook',
            'viber' => 'Viber',
            'whatsapp' => 'WhatsApp',
        ];
    }
}
