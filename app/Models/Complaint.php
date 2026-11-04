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

    protected $fillable = ['ticket', 'name', 'contact', 'topic', 'budget_line_id', 'message', 'status', 'response', 'responded_at', 'responded_by', 'is_demo'];

    protected function casts(): array
    {
        return ['responded_at' => 'datetime', 'is_demo' => 'boolean'];
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
