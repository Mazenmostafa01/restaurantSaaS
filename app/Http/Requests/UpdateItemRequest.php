<?php

namespace App\Http\Requests;

use App\Enums\ItemCategoryEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateItemRequest extends FormRequest
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
            'name' => ['required', 'string', Rule::unique('items', 'name')->ignore(request('item')->id), 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', new Enum(ItemCategoryEnum::class)],
            'description' => ['nullable', 'string'],
        ];
    }
}
