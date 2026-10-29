<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OtpVerifyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string'],
            'code'  => ['required', 'digits:' . config('marsaly.phone.otp_length')],
            'name'  => ['nullable', 'string', 'max:120'],
        ];
    }
}
