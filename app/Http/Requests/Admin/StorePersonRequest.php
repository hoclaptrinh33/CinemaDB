<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePersonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'full_name'      => ['required', 'string', 'max:200'],
            'birth_date'     => ['nullable', 'date', 'before:today'],
            'death_date'     => ['nullable', 'date', 'after:birth_date'],
            'biography'      => ['nullable', 'string'],
            'biography_vi'   => ['nullable', 'string'],
            'country_id'     => ['nullable', 'exists:countries,country_id'],
            'gender'         => ['nullable', Rule::in(['Male', 'Female', 'Other'])],
            'profile_image'  => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:3072'],
        ];
    }
}
