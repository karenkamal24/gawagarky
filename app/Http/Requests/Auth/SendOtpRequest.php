<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string'],
            'type' => ['required', 'in:phone_verification,email_verification,password_reset'],
        ];
    }

    public function messages(): array
    {
        return [
            'identifier.required' => 'رقم الهاتف أو البريد الإلكتروني مطلوب',
            'type.required' => 'نوع التحقق مطلوب',
            'type.in' => 'نوع التحقق غير صحيح',
        ];
    }
}