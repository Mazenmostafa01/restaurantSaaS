<?php

namespace App\Http\Requests;

use App\Enums\OrderTypeEnum;
use App\Models\Item;
use App\Services\TenantContext;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class OrderUpdateRequest extends FormRequest
{
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
        $tenantId = app(TenantContext::class)->id();
        $customerRule = Rule::exists('customers', 'id');

        if ($tenantId !== null) {
            $customerRule->where('restaurant_id', $tenantId);
        }

        return [
            'customer_id' => ['required_if:type,delivery', 'nullable', $customerRule],
            'type' => ['required', new Enum(OrderTypeEnum::class)],
            'items' => 'required|array|min:1',
            'items.*.selected' => 'required_with:items.*.quantity|in:on',
            'items.*.quantity' => 'required_with:items.*.selected|integer|min:1|max:100',
            'note' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required_if' => 'Please select a customer.',
            'type.required' => 'Please select an order type.',
            'type.in' => 'Order type must be one of: delivery, take away',
            'items.required' => 'You must select at least one item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);

            if (! is_array($items)) {
                return;
            }

            $itemIds = array_keys($items);

            if ($itemIds === []) {
                return;
            }

            $validItemCount = Item::whereIn('id', $itemIds)->count();

            if ($validItemCount !== count($itemIds)) {
                $validator->errors()->add('items', 'One or more selected items are invalid for this restaurant.');
            }
        });
    }
}
