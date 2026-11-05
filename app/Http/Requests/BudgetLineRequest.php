<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
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
            'sub_category' => ['nullable', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:255'],
            'volume' => ['nullable', 'string', 'max:50'],
            'amount' => ['required', 'numeric', 'min:0', 'max:10000000000000', 'decimal:0,2'],
            'funding_source' => ['nullable', 'string', 'max:50'],
            'source' => ['required', 'string', 'max:500'],
            'source_date' => ['nullable', 'date_format:Y-m-d'],
            'verification_status' => ['required', Rule::in(['verified', 'unverified', 'unavailable'])],
        ];
    }

    /**
     * Aturan formulir: sama dengan impor, ditambah pemeriksaan uraian ganda pada bidang dan tahun yang sama.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = self::budgetRules();
        $rules['description'][] = Rule::unique('budget_lines', 'description')
            ->where(fn (Builder $query) => $query->where('year', $this->integer('year'))->where('type', $this->text('type'))->where('category', $this->text('category')))
            ->ignore($this->route('budget'));

        return $rules;
    }

    public function messages(): array
    {
        return ['description.unique' => 'Uraian ini sudah ada pada bidang dan tahun yang sama. Ubah data yang sudah ada agar totalnya tidak terhitung dua kali.'];
    }

    public function attributes(): array
    {
        return ['year' => 'tahun anggaran', 'type' => 'jenis anggaran', 'category' => 'kategori', 'description' => 'uraian', 'amount' => 'nilai anggaran', 'source' => 'sumber data', 'source_date' => 'tanggal sumber', 'verification_status' => 'status verifikasi', 'sub_category' => 'sub-bidang', 'volume' => 'volume', 'funding_source' => 'sumber dana'];
    }

    private function text(string $key): string
    {
        $value = $this->input($key);

        return is_scalar($value) ? (string) $value : '';
    }
}
