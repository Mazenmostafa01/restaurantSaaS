<?php

namespace App\Http\Requests;

use App\Enums\ItemCategoryEnum;
use App\Traits\ScopesValidationToTenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddItemRequest extends FormRequest
{
    use ScopesValidationToTenant;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', $this->tenantUnique('items', 'name'), 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', Rule::enum(ItemCategoryEnum::class)],
            'description' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,png,jpeg', 'max:1024'],
        ];
    }
}
