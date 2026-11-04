<?php

namespace App\Models;

use Database\Factories\RealizationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu transaksi pengeluaran (realisasi) untuk sebuah kegiatan belanja.
 */
class Realization extends Model
{
    /** @use HasFactory<RealizationFactory> */
    use HasFactory;

    protected $fillable = ['budget_line_id', 'spent_on', 'amount', 'description', 'is_demo', 'user_id'];

    protected function casts(): array
    {
        return ['spent_on' => 'date', 'amount' => 'decimal:2', 'is_demo' => 'boolean'];
    }

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
