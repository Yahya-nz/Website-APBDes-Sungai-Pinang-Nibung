<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetLine extends Model
{
    use HasFactory;

    /** Status verifikasi data sesuai rancangan: terverifikasi, belum diverifikasi, belum tersedia. */
    public const STATUSES = ['verified' => 'Terverifikasi', 'unverified' => 'Belum diverifikasi', 'unavailable' => 'Belum tersedia'];

    protected $fillable = ['dataset_id', 'year', 'type', 'category', 'sub_category', 'description', 'volume', 'amount', 'funding_source', 'source', 'source_date', 'verification_status', 'is_demo'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'amount' => 'decimal:2', 'source_date' => 'date', 'is_demo' => 'boolean'];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function realizations(): HasMany
    {
        return $this->hasMany(Realization::class);
    }
}
