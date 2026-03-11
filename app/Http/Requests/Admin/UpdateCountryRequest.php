<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('country')->country_id;

        return [
            'iso_code'     => ['required', 'string', 'max:3', Rule::unique('countries', 'iso_code')->ignore($id, 'country_id')],
            'country_name' => ['required', 'string', 'max:100', Rule::unique('countries', 'country_name')->ignore($id, 'country_id')],
        ];
    }
}
