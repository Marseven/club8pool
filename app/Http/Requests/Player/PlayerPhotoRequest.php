<?php

namespace App\Http\Requests\Player;

use Illuminate\Foundation\Http\FormRequest;

class PlayerPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Une photo est requise.',
            'photo.file'     => 'Le fichier est invalide.',
            'photo.mimes'    => 'La photo doit être au format JPG, JPEG, PNG ou WEBP.',
            'photo.max'      => 'La photo ne doit pas dépasser 2 Mo.',
        ];
    }
}
