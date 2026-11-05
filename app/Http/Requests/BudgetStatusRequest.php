<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Menetapkan status verifikasi untuk beberapa baris anggaran sekaligus.
 */
class BudgetStatusRequest extends FormRequest
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
            'ids' => ['required', 'array', 'max:1000'],
            'ids.*' => ['integer', 'distinct', Rule::exists('budget_lines', 'id')],
            'verification_status' => ['required', Rule::in(['verified', 'unverified', 'unavailable'])],
        ];
    }

    public function attributes(): array
    {
        return ['ids' => 'baris anggaran', 'ids.*' => 'baris anggaran', 'verification_status' => 'status verifikasi'];
    }

    public function messages(): array
    {
        return ['ids.required' => 'Pilih minimal satu baris anggaran pada tabel.'];
    }
}
