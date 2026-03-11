<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'tmdb_id'      => ['nullable', 'integer', 'min:1', 'unique:genres,tmdb_id'],
            'genre_name'   => ['required', 'string', 'max:100', 'unique:genres,genre_name'],
            'genre_name_vi' => ['nullable', 'string', 'max:100'],
        ];
    }
}
