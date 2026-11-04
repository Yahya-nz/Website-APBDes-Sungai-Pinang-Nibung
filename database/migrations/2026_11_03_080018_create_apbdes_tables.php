<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_admin')->default(false);
        });
        Schema::create('datasets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedSmallInteger('year');
            $table->string('source', 500);
            $table->date('source_date')->nullable();
            $table->boolean('is_demo')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('budget_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dataset_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->string('type', 20);
            $table->string('category', 150);
            $table->string('description', 255);
            $table->decimal('amount', 18, 2);
            $table->decimal('realization', 18, 2)->nullable();
            $table->string('source', 500);
            $table->date('source_date')->nullable();
            $table->string('verification_status', 20)->default('unverified');
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->index(['year', 'type']);
            $table->index(['year', 'verification_status']);
        });
        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('action', 50);
            $table->string('description', 500);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('datasets');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_admin');
        });
    }
};
