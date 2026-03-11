<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
    }

    public function rules(): array
    {
        return [
            'rating'      => ['required', 'integer', 'min:1', 'max:10'],
            'review_text' => ['nullable', 'string', 'max:5000'],
            'has_spoilers' => ['sometimes', 'boolean'],
        ];
    }
}
