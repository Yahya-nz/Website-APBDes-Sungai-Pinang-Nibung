<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-data') ?? false;
    }

    public function rules(): array
    {
        return ['file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'extensions:csv,xlsx', 'max:2048'], 'source' => ['nullable', 'string', 'max:500'], 'year' => ['nullable', 'integer', 'between:2000,2100'], 'source_date' => ['nullable', 'date_format:Y-m-d'], 'is_demo' => ['sometimes', 'boolean']];
    }
}
