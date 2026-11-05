<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Informasi sumber sebuah dataset: nama, dokumen sumber, dan tanggal pembaruannya.
 */
class DatasetRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', 'max:500'],
            'source_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'apply_to_lines' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama dataset', 'source' => 'nama / jenis sumber', 'source_date' => 'tanggal pembaruan'];
    }
}
