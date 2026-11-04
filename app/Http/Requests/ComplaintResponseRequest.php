<?php

namespace App\Http\Requests;

use App\Models\Complaint;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ComplaintResponseRequest extends FormRequest
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
            'status' => ['required', Rule::in(array_keys(Complaint::STATUSES))],
            'response' => ['nullable', 'string', 'max:2000', 'required_if:status,resolved'],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'status', 'response' => 'tanggapan'];
    }

    public function messages(): array
    {
        return ['response.required_if' => 'Tulis tanggapan sebelum menandai pengaduan selesai.'];
    }
}
