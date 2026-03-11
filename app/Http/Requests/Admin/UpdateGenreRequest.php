<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('genre')->genre_id;

        return [
            'tmdb_id'       => ['nullable', 'integer', 'min:1', Rule::unique('genres', 'tmdb_id')->ignore($id, 'genre_id')],
            'genre_name'    => ['required', 'string', 'max:100', Rule::unique('genres', 'genre_name')->ignore($id, 'genre_id')],
            'genre_name_vi' => ['nullable', 'string', 'max:100'],
        ];
    }
}
