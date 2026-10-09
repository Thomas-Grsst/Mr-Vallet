<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVgpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'last_vgp_at' => ['required', 'date_format:Y-m-d'],
        ];
    }

    public function messages(): array
    {
        return [
            'last_vgp_at.required' => 'Indiquez la date de la VGP.',
        ];
    }
}
