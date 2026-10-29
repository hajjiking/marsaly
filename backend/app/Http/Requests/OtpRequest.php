<?php

namespace App\Http\Requests;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OtpRequest extends FormRequest
{
    public function rules(): array
    {
        return ['phone' => ['required', 'string']];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if (!Phone::isValidMoroccan($this->input('phone'))) {
                $v->errors()->add('phone', __('api.invalid_phone'));
            }
        });
    }
}
