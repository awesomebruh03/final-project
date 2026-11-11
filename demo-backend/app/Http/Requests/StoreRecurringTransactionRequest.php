<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRecurringTransactionRequest extends FormRequest
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
            'type' => ['required', 'in:expense,income'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'category' => ['required', 'string', 'max:50'],
            'note' => ['nullable', 'string'],
            'nature' => ['nullable', 'in:fixed,variable'],
            'frequency' => ['required', 'in:daily,weekly,monthly,yearly'],
            'interval' => ['nullable', 'integer', 'min:1', 'max:12'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ];
    }
}
