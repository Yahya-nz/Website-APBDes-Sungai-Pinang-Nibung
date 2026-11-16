<?php

namespace App\Models;

use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengaduan, pertanyaan, atau saran dari warga tentang anggaran desa.
 */
class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    public const TOPICS = ['question' => 'Pertanyaan', 'suggestion' => 'Saran', 'complaint' => 'Pengaduan'];

    public const STATUSES = ['new' => 'Baru', 'in_progress' => 'Diproses', 'resolved' => 'Selesai'];

    protected $fillable = ['ticket', 'name', 'contact', 'contact_key', 'recovery_pin', 'topic', 'budget_line_id', 'message', 'status', 'response', 'responded_at', 'responded_by', 'is_demo'];

    protected $hidden = ['recovery_pin'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime', 'is_demo' => 'boolean', 'recovery_pin' => 'hashed'];
    }

    /**
     * Bentuk baku kontak untuk pencocokan pemulihan tiket: email huruf kecil, nomor HP angka saja
     * dengan awalan 62 diubah menjadi 0 (misalnya +62 812-3456 menjadi 08123456).
     */
    public static function contactKey(?string $contact): ?string
    {
        $contact = trim((string) $contact);
        if ($contact === '') {
            return null;
        }
        if (str_contains($contact, '@')) {
            return mb_strtolower($contact);
        }
        $digits = preg_replace('/\D+/', '', $contact) ?? '';
        if (str_starts_with($digits, '62')) {
            $digits = '0'.substr($digits, 2);
        }

        return $digits !== '' ? $digits : mb_strtolower($contact);
    }

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    /**
     * Nomor tiket pendek yang mudah disalin warga, misalnya ADU-2611-7K3P.
     */
    public static function newTicket(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $suffix = '';
            for ($i = 0; $i < 4; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $ticket = 'ADU-'.now()->format('ym').'-'.$suffix;
        } while (self::where('ticket', $ticket)->exists());

        return $ticket;
    }
}
