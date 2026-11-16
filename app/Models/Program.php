<?php

namespace App\Models;

use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rencana program atau kegiatan desa yang bisa dinilai warga lewat voting setuju / tidak setuju.
 */
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    public const STATUSES = ['proposed' => 'Usulan', 'planned' => 'Direncanakan', 'ongoing' => 'Berjalan', 'done' => 'Selesai'];

    public const CHOICES = ['agree' => 'Setuju', 'disagree' => 'Tidak setuju'];

    protected $fillable = ['title', 'description', 'category', 'location', 'year', 'estimated_budget', 'status', 'voting_open', 'voting_ends_at', 'user_id'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'estimated_budget' => 'decimal:2', 'voting_open' => 'boolean', 'voting_ends_at' => 'date'];
    }

    public function votes(): HasMany
    {
        return $this->hasMany(ProgramVote::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Menambahkan jumlah suara setuju dan tidak setuju pada setiap program.
     *
     * @param  Builder<Program>  $query
     */
    public function scopeWithVoteCounts(Builder $query): void
    {
        $query->withCount([
            'votes as agree_count' => fn (Builder $votes) => $votes->where('choice', 'agree'),
            'votes as disagree_count' => fn (Builder $votes) => $votes->where('choice', 'disagree'),
        ]);
    }

    /**
     * Voting dibuka bila pengelola mengaktifkannya dan batas waktunya (kalau ada) belum lewat.
     */
    public function isVotingOpen(): bool
    {
        return $this->voting_open && ($this->voting_ends_at === null || ! $this->voting_ends_at->copy()->endOfDay()->isPast());
    }

    /**
     * Hasil voting dalam jumlah suara dan persentase. Persentase tidak dihitung bila belum ada suara.
     *
     * @return array{agree: int, disagree: int, total: int, agree_percent: float|null, disagree_percent: float|null}
     */
    public function results(): array
    {
        $agree = (int) ($this->agree_count ?? $this->votes()->where('choice', 'agree')->count());
        $disagree = (int) ($this->disagree_count ?? $this->votes()->where('choice', 'disagree')->count());
        $total = $agree + $disagree;
        $agreePercent = $total > 0 ? round($agree / $total * 100, 1) : null;

        return [
            'agree' => $agree,
            'disagree' => $disagree,
            'total' => $total,
            'agree_percent' => $agreePercent,
            'disagree_percent' => $agreePercent === null ? null : round(100 - $agreePercent, 1),
        ];
    }
}
