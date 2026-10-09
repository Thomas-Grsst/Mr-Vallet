<?php

namespace App\Http\Requests;

use App\Models\KeyAccount;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreKeyAccountRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) {
                    $existing = KeyAccount::matching((string) $value);

                    if ($existing !== null) {
                        $fail("{$existing->name} est déjà un grand compte.");
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Indiquez le nom de l\'entreprise.',
        ];
    }
}
