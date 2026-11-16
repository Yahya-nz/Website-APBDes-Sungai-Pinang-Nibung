<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Memulihkan nomor tiket yang hilang dengan kontak dan PIN yang diisi saat mengirim pesan.
 */
class ComplaintRecoveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'contact' => ['required', 'string', 'max:100'],
            'recovery_pin' => ['required', 'digits_between:4,6'],
        ];
    }

    public function attributes(): array
    {
        return ['contact' => 'nomor HP / email', 'recovery_pin' => 'PIN pemulihan'];
    }
}
