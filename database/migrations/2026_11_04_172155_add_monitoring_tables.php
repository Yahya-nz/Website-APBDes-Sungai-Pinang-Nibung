<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_lines', function (Blueprint $table): void {
            $table->string('sub_category', 150)->nullable()->after('category');
            $table->string('volume', 50)->nullable()->after('description');
            $table->string('funding_source', 50)->nullable()->after('amount');
            $table->dropColumn('realization');
        });

        Schema::create('realizations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('budget_line_id')->constrained()->cascadeOnDelete();
            $table->date('spent_on');
            $table->decimal('amount', 18, 2);
            $table->string('description', 255);
            $table->boolean('is_demo')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index('spent_on');
        });

        Schema::create('complaints', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket', 20)->unique();
            $table->string('name', 100)->nullable();
            $table->string('contact', 100)->nullable();
            $table->string('topic', 20);
            $table->foreignId('budget_line_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->string('status', 20)->default('new');
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_demo')->default(false);
            $table->timestamps();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('realizations');
        Schema::table('budget_lines', function (Blueprint $table): void {
            $table->dropColumn(['sub_category', 'volume', 'funding_source']);
            $table->decimal('realization', 18, 2)->nullable();
        });
    }
};
