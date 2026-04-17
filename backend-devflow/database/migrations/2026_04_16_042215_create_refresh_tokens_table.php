<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();

            // DSA — Hash Map with O(1) lookup:
            // token_hash stores hash('sha256', $plainTextToken).
            // The unique index makes every lookup O(log n) via B-tree,
            // effectively O(1) for single-row lookups.
            // The plain text token is NEVER stored — only the hash.
            // A database breach exposes hashes, not valid tokens.
            $table->string('token_hash', 64)->unique();

            $table->timestamp('expires_at');
            $table->boolean('revoked')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
