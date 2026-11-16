<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kontak yang dinormalkan dan PIN (di-hash) agar warga bisa memulihkan nomor tiket yang hilang.
     */
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table): void {
            $table->string('contact_key', 120)->nullable()->after('contact')->index();
            $table->string('recovery_pin')->nullable()->after('contact_key');
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table): void {
            $table->dropIndex(['contact_key']);
            $table->dropColumn(['contact_key', 'recovery_pin']);
        });
    }
};
