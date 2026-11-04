<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetLine extends Model
{
    use HasFactory;

    protected $fillable = ['dataset_id', 'year', 'type', 'category', 'description', 'amount', 'realization', 'source', 'source_date', 'verification_status', 'is_demo'];

    protected function casts(): array
    {
        return ['year' => 'integer', 'amount' => 'decimal:2', 'realization' => 'decimal:2', 'source_date' => 'date', 'is_demo' => 'boolean'];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }
}
