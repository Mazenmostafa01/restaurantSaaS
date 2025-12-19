<?php

namespace App\Http\Requests;

use App\Enums\OrderTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class OrderUpdateRequest extends FormRequest
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
            'customer_id' => 'required_if:type,delivery',
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
}
