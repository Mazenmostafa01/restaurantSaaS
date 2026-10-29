<?php

namespace App\Http\Requests;

use App\Enums\OrderTypeEnum;
use App\Models\Item;
use App\Traits\ScopesValidationToTenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrderCreateRequest extends FormRequest
{
    use ScopesValidationToTenant;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');

        if (! is_array($items)) {
            return;
        }

        $filteredItems = array_filter(
            $items,
            static fn (mixed $item): bool => is_array($item)
                && (($item['selected'] ?? null) !== null || ($item['quantity'] ?? null) !== null)
        );

        $this->merge(['items' => $filteredItems]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isDelivery = $this->input('type') === OrderTypeEnum::DELIVERY->value;

        return [
            'customer_id' => [
                $isDelivery ? 'required' : 'nullable',
                $this->tenantExists('customers', 'id'),
            ],
            'type' => ['required', Rule::enum(OrderTypeEnum::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.selected' => ['required', 'in:on'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'A customer is required for delivery orders.',
            'type.required' => 'Please select an order type.',
            'type.in' => 'Order type must be one of: delivery, take away',
            'items.required' => 'You must select at least one item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $items = $this->input('items', []);

            if (! is_array($items) || $items === []) {
                return;
            }

            $itemIds = array_keys($items);
            $validItemCount = Item::whereIn('id', $itemIds)->count();

            if ($validItemCount !== count($itemIds)) {
                $validator->errors()->add('items', 'One or more selected items are invalid for this restaurant.');
            }
        });
    }
}
