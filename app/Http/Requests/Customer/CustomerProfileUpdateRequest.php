<?php

namespace App\Http\Requests\Customer;

use App\Traits\ScopesValidationToTenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerProfileUpdateRequest extends FormRequest
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
        $customer = $this->user();

        return [
            'name' => ['sometimes', 'string', 'max:50'],
            'phone_number' => [
                'sometimes',
                'string',
                'max:20',
                $this->tenantUnique('customers', 'phone_number')->ignore($customer?->id),
            ],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.unique' => 'This phone number is already in use at this restaurant.',
        ];
    }
}
