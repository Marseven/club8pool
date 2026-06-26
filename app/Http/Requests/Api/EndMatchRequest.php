<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class EndMatchRequest extends FormRequest
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
            'referee_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
