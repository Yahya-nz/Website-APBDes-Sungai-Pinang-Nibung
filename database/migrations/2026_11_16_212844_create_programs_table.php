<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rencana program atau kegiatan desa yang dibuka untuk pendapat warga.
     */
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 150);
            $table->text('description');
            $table->string('category', 150)->nullable();
            $table->string('location', 150)->nullable();
            $table->unsignedSmallInteger('year');
            $table->decimal('estimated_budget', 18, 2)->nullable();
            $table->string('status', 20)->default('proposed');
            $table->boolean('voting_open')->default(true);
            $table->date('voting_ends_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['year', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};
