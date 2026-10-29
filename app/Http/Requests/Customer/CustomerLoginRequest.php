<?php

namespace App\Http\Requests\Customer;

use App\Services\CustomerAuthService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CustomerLoginRequest extends FormRequest
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
            'login',
            $email,
            (string) $this->ip()
        );

        $authService->ensureNotRateLimited($throttleKey, 'login');
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
