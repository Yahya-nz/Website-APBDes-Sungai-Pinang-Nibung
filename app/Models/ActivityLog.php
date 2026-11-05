<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    /**
     * Jenis aktivitas beserta label dan ikonnya untuk riwayat.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const ACTIONS = [
        'create' => ['Menambah anggaran', 'plus'],
        'update' => ['Memperbarui anggaran', 'pencil'],
        'delete' => ['Menghapus data', 'trash-2'],
        'import' => ['Mengimpor data', 'upload'],
        'verify' => ['Menetapkan status verifikasi', 'shield-check'],
        'dataset' => ['Memperbarui sumber dataset', 'file-spreadsheet'],
        'seed' => ['Menyiapkan data awal', 'database'],
        'realization' => ['Mencatat pengeluaran', 'receipt-text'],
        'complaint' => ['Menanggapi pengaduan', 'message-square-text'],
        'user' => ['Mengelola pengguna', 'user-cog'],
        'password' => ['Mengganti kata sandi', 'key-round'],
    ];

    protected $fillable = ['user_id', 'year', 'action', 'description'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::ACTIONS[$this->action][0] ?? $this->action;
    }

    public function icon(): string
    {
        return self::ACTIONS[$this->action][1] ?? 'circle';
    }
}
