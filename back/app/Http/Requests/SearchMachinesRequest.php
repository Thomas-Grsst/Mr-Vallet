<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchMachinesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['nullable', 'string'],
            'from' => ['nullable', 'date_format:Y-m-d', 'required_with:to'],
            'to' => ['nullable', 'date_format:Y-m-d', 'required_with:from', 'after_or_equal:from'],
        ];
    }

    public function messages(): array
    {
        return [
            'to.after_or_equal' => 'La date de fin doit être égale ou postérieure à la date de début.',
        ];
    }
}
