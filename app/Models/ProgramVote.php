<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu suara warga untuk sebuah rencana program. Pemilih dikenali dari kunci acak di perangkatnya, bukan identitas pribadi.
 */
class ProgramVote extends Model
{
    protected $fillable = ['program_id', 'choice', 'voter_key'];

    protected $hidden = ['voter_key'];

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
