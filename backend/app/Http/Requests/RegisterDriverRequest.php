<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDriverRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'vehicle_type'        => ['required', 'in:motorcycle,car,bicycle,van'],
            'vehicle_model'       => ['nullable', 'string', 'max:120'],
            'plate_number'        => ['nullable', 'string', 'max:30'],
            'id_document'         => ['required', 'image', 'max:5120'],
            'vehicle_document'    => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }
}
