<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCountryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'iso_code'     => ['required', 'string', 'max:3', 'unique:countries,iso_code'],
            'country_name' => ['required', 'string', 'max:100', 'unique:countries,country_name'],
        ];
    }
}
