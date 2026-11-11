<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['nullable', 'date_format:H:i'],
            'category' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
            'nature' => ['nullable', 'in:fixed,variable'],
        ];
    }
}
