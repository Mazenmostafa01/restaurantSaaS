<?php

namespace App\Http\Requests\Customer;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerOrderParseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'text' => ['nullable', 'required_without:audio', 'string', 'max:2000'],
            'audio' => [
                'nullable',
                'required_without:text',
                'file',
                'max:3072',
                'mimetypes:audio/webm,audio/mp4,audio/ogg,audio/mpeg,audio/wav,video/webm',
            ],
            'preferred_language' => ['nullable', 'string', 'max:20'],
        ];
    }
}
