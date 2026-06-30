<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class PlayerLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login_name' => ['required', 'string', 'max:100'],
            'password'   => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'login_name.required' => 'Identifiant requis.',
            'password.required'   => 'Mot de passe requis.',
        ];
    }
}
