<?php

namespace App\Http\Requests;

use App\Services\ReservationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVgpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'last_vgp_at' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.ReservationRules::today()->toDateString()],
        ];
    }

    public function messages(): array
    {
        return [
            'last_vgp_at.required' => 'Indiquez la date de la VGP.',
            'last_vgp_at.before_or_equal' => 'Une VGP enregistrée doit être réalisée : sa date est le '.ReservationRules::today()->format('d/m/Y').' ou avant. Pour une VGP future, prévoyez un passage en atelier.',
        ];
    }
}
