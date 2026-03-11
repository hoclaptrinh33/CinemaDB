<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('language')->language_id;

        return [
            'iso_code'      => ['required', 'string', 'max:5', Rule::unique('languages', 'iso_code')->ignore($id, 'language_id')],
            'language_name' => ['required', 'string', 'max:100', Rule::unique('languages', 'language_name')->ignore($id, 'language_id')],
        ];
    }
}
