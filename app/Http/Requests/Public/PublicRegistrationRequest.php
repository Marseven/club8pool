<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class PublicRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name'  => ['required', 'string', 'max:80'],
            'email'      => ['nullable', 'email', 'max:160'],
            'phone'      => ['nullable', 'string', 'max:30'],
            'club_name'  => ['nullable', 'string', 'max:100'],
            'fgb_card'   => ['nullable', 'string', 'max:30'],
        ];
    }
}
