<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date_format:Y-m-d'],
            'ends_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'purchase_order' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.required' => 'Indiquez la date de début.',
            'ends_at.required' => 'Indiquez la date de fin.',
            'ends_at.after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.',
        ];
    }
}
