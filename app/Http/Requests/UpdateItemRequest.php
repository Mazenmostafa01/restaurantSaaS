<?php

namespace App\Http\Requests;

use App\Enums\ItemCategoryEnum;
use App\Models\Item;
use App\Traits\ScopesValidationToTenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
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
        $item = $this->route('item');
        $itemId = $item instanceof Item ? $item->id : $item;

        return [
            'name' => ['required', 'string', $this->tenantUnique('items', 'name')->ignore($itemId), 'max:100'],
            'price' => ['required', 'numeric', 'min:0'],
            'category' => ['required', Rule::enum(ItemCategoryEnum::class)],
            'description' => ['nullable', 'string'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,png,jpeg', 'max:1024'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => [
                'integer',
                Rule::exists('attachments', 'id')
                    ->where('attachment_type', Item::class)
                    ->where('attachment_id', $itemId),
            ],
        ];
    }
}
