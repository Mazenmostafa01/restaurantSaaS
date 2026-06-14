<?php

namespace App\Http\Requests;

use App\Enums\ItemCategoryEnum;
use App\Models\Item;
use App\Services\TenantContext;
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
        $item = $this->route('item');
        $itemId = $item instanceof Item ? $item->id : $item;
        $tenantId = app(TenantContext::class)->id();
        $nameRule = Rule::unique('items', 'name')->ignore($itemId);

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
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => [
                'integer',
                Rule::exists('attachments', 'id')->where('attachment_type', Item::class),
            ],
        ];
    }
}
