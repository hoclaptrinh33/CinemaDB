<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('role')->role_id;

        return [
            'role_name' => ['required', 'string', 'max:50', Rule::unique('roles', 'role_name')->ignore($id, 'role_id')],
        ];
    }
}
