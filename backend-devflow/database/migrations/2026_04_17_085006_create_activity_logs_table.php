<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->json('meta')->nullable();
            $table->timestamps();

            // Index for querying logs by task — O(log n) lookup
            $table->index(['task_id', 'created_at'], 'idx_logs_task_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};