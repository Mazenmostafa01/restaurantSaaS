<?php

namespace App\Http\Requests;

use App\Traits\ScopesValidationToTenant;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuickCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50'],
            'phone_number' => ['required', 'string', 'max:20', $this->tenantUnique('customers', 'phone_number')],
            'address' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Customer name is required.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.unique' => 'A customer with this phone number already exists.',
        ];
    }
}
