<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Suara warga untuk rencana program. Satu perangkat satu suara per program (voter_key unik).
     */
    public function up(): void
    {
        Schema::create('program_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->string('choice', 10);
            $table->string('voter_key', 64);
            $table->timestamps();
            $table->unique(['program_id', 'voter_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_votes');
    }
};
