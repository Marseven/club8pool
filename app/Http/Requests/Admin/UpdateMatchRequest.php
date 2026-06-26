<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score_a'   => ['required', 'integer', 'min:0'],
            'score_b'   => ['required', 'integer', 'min:0'],
            'is_draw'   => ['boolean'],
            'warning_a' => ['boolean'],
            'warning_b' => ['boolean'],
            'note'      => ['nullable', 'string'],
        ];
    }
}
