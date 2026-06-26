<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreFrameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function wantsJson(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'winner'        => ['required', 'in:A,B,draw'],
            'warning_a'     => ['sometimes', 'boolean'],
            'warning_b'     => ['sometimes', 'boolean'],
            'foul_a'        => ['sometimes', 'boolean'],
            'foul_b'        => ['sometimes', 'boolean'],
            'safety_a'      => ['sometimes', 'boolean'],
            'safety_b'      => ['sometimes', 'boolean'],
            'break_and_run' => ['sometimes', 'boolean'],
            'is_break'      => ['sometimes', 'boolean'],
        ];
    }
}
