<?php

namespace App\Http\Requests\Customer;

use App\Enums\OrderTypeEnum;
use App\Traits\ScopesValidationToTenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerOrderStoreRequest extends FormRequest
{
    use ScopesValidationToTenant;

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
            'type' => ['required', Rule::enum(OrderTypeEnum::class)],
            'note' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => [
                'required',
                'integer',
                'distinct',
                $this->tenantExists('items', 'id', withoutTrashed: true),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
