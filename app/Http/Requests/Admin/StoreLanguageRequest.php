<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'iso_code'      => ['required', 'string', 'max:5', 'unique:languages,iso_code'],
            'language_name' => ['required', 'string', 'max:100', 'unique:languages,language_name'],
        ];
    }
}
