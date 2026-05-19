<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:4', 'regex:/^[0-9]{4}$/'],
            'type' => ['required', 'in:phone_verification,email_verification,password_reset'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'رقم الهاتف أو البريد الإلكتروني مطلوب',
            'otp.required' => 'رمز التحقق مطلوب',
            'otp.size' => 'رمز التحقق يجب أن يكون 4 أرقام',
            'otp.regex' => 'رمز التحقق يجب أن يحتوي على أرقام فقط',
            'type.required' => 'نوع التحقق مطلوب',
        ];
    }
}