<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BudgetLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-data') ?? false;
    }

    public static function budgetRules(): array
    {
        return [
            'year' => ['required', 'integer', 'between:2000,2100'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:10000000000000', 'decimal:0,2'],
            'realization' => ['nullable', 'numeric', 'min:0', 'max:10000000000000', 'decimal:0,2'],
            'source' => ['required', 'string', 'max:500'],
            'source_date' => ['nullable', 'date_format:Y-m-d'],
            'verification_status' => ['required', Rule::in(['verified', 'unverified', 'unavailable'])],
        ];
    }

    public function rules(): array
    {
        return self::budgetRules();
    }

    public function attributes(): array
    {
        return ['year' => 'tahun anggaran', 'type' => 'jenis anggaran', 'category' => 'kategori', 'description' => 'uraian', 'amount' => 'nilai anggaran', 'source' => 'sumber data', 'source_date' => 'tanggal sumber', 'verification_status' => 'status verifikasi', 'realization' => 'realisasi'];
    }
}
