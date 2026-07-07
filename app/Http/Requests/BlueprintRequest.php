<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BlueprintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'tone'           => ['required', 'string', 'max:255'],
            'max_hashtags'   => ['required', 'integer', 'min:0', 'max:10'],
            'max_characters' => ['required', 'integer', 'min:1', 'max:280'],
            'regle_supp'     => ['nullable', 'string'],
        ];
    }
}