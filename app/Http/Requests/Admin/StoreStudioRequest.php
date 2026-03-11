<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'studio_name'  => ['required', 'string', 'max:200', 'unique:studios,studio_name'],
            'country_id'   => ['nullable', 'exists:countries,country_id'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:' . date('Y')],
            'website_url'  => ['nullable', 'url', 'max:500'],
            'logo_image'   => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }
}
