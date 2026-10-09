<?php

namespace App\Http\Requests;

use App\Services\ReservationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'machine_ref' => ['required', 'string', 'exists:machines,ref'],
            'client' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.ReservationRules::today()->toDateString()],
            'ends_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'machine_ref.required' => 'Choisissez une machine.',
            'machine_ref.exists' => 'Cette machine n\'existe pas.',
            'client.required' => 'Indiquez le client.',
            'starts_at.required' => 'Indiquez la date de début.',
            'starts_at.after_or_equal' => 'On ne peut pas réserver dans le passé : la date de début doit être le '.ReservationRules::today()->format('d/m/Y').' ou après.',
            'ends_at.required' => 'Indiquez la date de fin.',
            'ends_at.after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.',
        ];
    }
}
