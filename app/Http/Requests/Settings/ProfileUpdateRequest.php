<?php

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
            ],
            'avatar' => ['nullable', 'image', 'max:4096', 'mimes:jpeg,png,jpg,webp'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.max' => 'Nama maksimal 255 karakter.',
            'email.max' => 'Email maksimal 255 karakter.',
            'email.email' => 'Format email tidak valid.',
            'avatar.image' => 'Foto profil harus berupa gambar.',
            'avatar.max' => 'Ukuran foto profil maksimal 4MB.',
            'avatar.mimes' => 'Foto profil harus berupa file dengan ekstensi jpeg, png, jpg, atau webp.',
        ];
    }
}
