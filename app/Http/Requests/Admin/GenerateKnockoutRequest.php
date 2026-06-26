<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GenerateKnockoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pairs'                  => ['required', 'array', 'min:1'],
            'pairs.*.player_a_id'    => ['required', 'integer', 'exists:players,id'],
            'pairs.*.player_b_id'    => ['required', 'integer', 'exists:players,id'],
        ];
    }
}
