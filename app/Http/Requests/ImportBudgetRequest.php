<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-data') ?? false;
    }

    /**
     * Berkas beserta informasi sumbernya. Periode, nama sumber, dan tanggal pembaruan wajib dicatat
     * agar setiap dataset bisa ditelusuri kembali ke dokumen asalnya.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'extensions:csv,xlsx', 'max:2048'],
            'year' => ['required', 'integer', 'between:2000,2100'],
            'source' => ['required', 'string', 'max:500'],
            'source_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'is_demo' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['file' => 'berkas', 'year' => 'periode anggaran', 'source' => 'nama / jenis sumber', 'source_date' => 'tanggal pembaruan'];
    }
}
