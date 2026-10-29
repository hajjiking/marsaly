<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterMerchantRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'business_name'      => ['required', 'string', 'max:160'],
            'category_id'        => ['required', 'exists:categories,id'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'address'            => ['required', 'string', 'max:255'],
            'neighborhood'       => ['nullable', 'string', 'max:120'],
            'lat'                => ['required', 'numeric', 'between:-90,90'],
            'lng'                => ['required', 'numeric', 'between:-180,180'],
            'licence_number'     => ['nullable', 'string', 'max:60'],
            'id_document'        => ['nullable', 'image', 'max:5120'],
            'licence_document'   => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'prep_time_minutes'  => ['nullable', 'integer', 'min:0', 'max:180'],
        ];
    }
}
