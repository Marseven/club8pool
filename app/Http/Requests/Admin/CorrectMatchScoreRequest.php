<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CorrectMatchScoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'score_a'           => ['required', 'integer', 'min:0', 'max:25'],
            'score_b'           => ['required', 'integer', 'min:0', 'max:25'],
            'correction_reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }
}
