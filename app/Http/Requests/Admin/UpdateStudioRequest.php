<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $studioId = $this->route('studio')?->studio_id;

        return [
            'studio_name'  => ['required', 'string', 'max:200', Rule::unique('studios', 'studio_name')->ignore($studioId, 'studio_id')],
            'country_id'   => ['nullable', 'exists:countries,country_id'],
            'founded_year' => ['nullable', 'integer', 'min:1800', 'max:' . date('Y')],
            'website_url'  => ['nullable', 'url', 'max:500'],
            'logo_image'   => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }
}
