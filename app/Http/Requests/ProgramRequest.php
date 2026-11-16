<?php

namespace App\Http\Requests;

use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Formulir rencana program desa yang diisi perangkat desa.
 */
class ProgramRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'min:15', 'max:2000'],
            'category' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'estimated_budget' => ['nullable', 'numeric', 'min:0', 'max:10000000000000', 'decimal:0,2'],
            'status' => ['required', Rule::in(array_keys(Program::STATUSES))],
            'voting_open' => ['sometimes', 'boolean'],
            'voting_ends_at' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'nama program', 'description' => 'uraian', 'category' => 'bidang', 'location' => 'lokasi', 'year' => 'tahun rencana', 'estimated_budget' => 'perkiraan anggaran', 'status' => 'status', 'voting_ends_at' => 'batas voting'];
    }
}
