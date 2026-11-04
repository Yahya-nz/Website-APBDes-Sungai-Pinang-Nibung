<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RealizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-data') ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'budget_line_id' => ['required', 'integer', Rule::exists('budget_lines', 'id')->where('type', 'expense')],
            'spent_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:10000000000000', 'decimal:0,2'],
            'description' => ['required', 'string', 'max:255'],
            'is_demo' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['budget_line_id' => 'kegiatan', 'spent_on' => 'tanggal', 'amount' => 'nominal', 'description' => 'keterangan'];
    }

    public function messages(): array
    {
        return ['spent_on.before_or_equal' => 'Tanggal pengeluaran tidak boleh di masa depan.'];
    }
}
