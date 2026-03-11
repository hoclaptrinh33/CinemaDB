<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTitleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'title_name'           => ['required', 'string', 'max:300'],
            'title_type'           => ['required', Rule::in(['MOVIE', 'SERIES', 'EPISODE'])],
            'release_date'         => ['nullable', 'date'],
            'runtime_mins'         => ['nullable', 'integer', 'min:1'],
            'description'          => ['nullable', 'string'],
            'original_language_id' => ['nullable', 'exists:languages,language_id'],
            'status'               => ['nullable', Rule::in(['Rumored', 'Post Production', 'Released', 'Canceled'])],
            'visibility'           => ['required', Rule::in(['PUBLIC', 'HIDDEN', 'COPYRIGHT_STRIKE', 'GEO_BLOCKED'])],
            'poster_image'         => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'backdrop_image'       => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:10240'],
            'trailer_url'          => ['nullable', 'url', 'max:500'],
            'budget'               => ['nullable', 'integer', 'min:0'],
            'revenue'              => ['nullable', 'integer', 'min:0'],
            'studio_ids'           => ['nullable', 'array'],
            'studio_ids.*'         => ['exists:studios,studio_id'],
        ];
    }
}
