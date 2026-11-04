<?php

namespace App\Http\Requests;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pengaduan warga. Tidak perlu login; kolom "website" adalah jebakan robot.
 */
class ComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'topic' => ['required', Rule::in(array_keys(Complaint::TOPICS))],
            'budget_line_id' => ['nullable', 'integer', Rule::exists('budget_lines', 'id')],
            'message' => ['required', 'string', 'min:15', 'max:2000'],
            'name' => ['nullable', 'string', 'max:100'],
            'contact' => ['nullable', 'string', 'max:100'],
            'website' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return ['topic' => 'jenis', 'budget_line_id' => 'kegiatan', 'message' => 'isi pesan', 'name' => 'nama', 'contact' => 'kontak'];
    }

    public function messages(): array
    {
        return ['website.prohibited' => 'Pesan tidak dapat dikirim.'];
    }
}
