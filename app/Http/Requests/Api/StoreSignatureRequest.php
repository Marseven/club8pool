<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSignatureRequest extends FormRequest
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
            'player_id'      => ['required', 'integer', 'exists:players,id'],
            'signature_data' => ['nullable', 'string', 'max:200000'],
        ];
    }
}
