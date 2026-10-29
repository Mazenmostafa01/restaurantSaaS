<?php

namespace App\Http\Requests\Customer;

use App\Services\CustomerAuthService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CustomerRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $authService = app(CustomerAuthService::class);
        $rawEmail = $this->input('email');
        $email = is_string($rawEmail) ? $rawEmail : '';
        $throttleKey = $authService->buildThrottleKey(
            'register',
            $email,
            (string) $this->ip()
        );

        $authService->ensureNotRateLimited($throttleKey, 'registration');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'address' => ['required', 'string', 'max:100'],
        ];
    }
}
