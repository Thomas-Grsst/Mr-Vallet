<?php

namespace App\Http\Requests;

use App\Services\ReservationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkshopPeriodRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'starts_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.ReservationRules::today()->toDateString()],
            'ends_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.required' => 'Indiquez la date de début du passage en atelier.',
            'starts_at.after_or_equal' => 'Le passage en atelier commence le '.ReservationRules::today()->format('d/m/Y').' ou après.',
            'ends_at.required' => 'Indiquez la date de fin du passage en atelier.',
            'ends_at.after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.',
        ];
    }
}
