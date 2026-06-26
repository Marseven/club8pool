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
            'birthdate'  => ['required', 'date'],
            'fgb_card'   => ['required', 'string', 'max:30'],
            'phone'      => ['required', 'string', 'max:30'],
            'email'      => ['required', 'email', 'max:160'],
            'address'    => ['required', 'string', 'max:255'],
            'club_id'    => ['nullable', 'exists:clubs,id'],
            'cue'        => ['nullable', 'string', 'max:100'],
        ];
    }
}
