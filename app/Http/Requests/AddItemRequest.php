<?php

namespace App\Http\Requests;

use App\Enums\ItemCategoryEnum;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class AddItemRequest extends FormRequest
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
        $tenantId = app(TenantContext::class)->id();
        $nameRule = Rule::unique('items', 'name');

        if ($tenantId !== null) {
            $nameRule->where('restaurant_id', $tenantId);
        }

        return [
            'name' => ['required', 'string', $nameRule, 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', new Enum(ItemCategoryEnum::class)],
            'description' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,png,jpeg', 'max:1024'],
        ];
    }
}
