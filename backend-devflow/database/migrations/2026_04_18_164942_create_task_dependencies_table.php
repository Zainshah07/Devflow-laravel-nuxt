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
         // DSA — Adjacency list representation of a directed graph:
        // This table stores directed edges in a graph where tasks are nodes.
        // task_id ← depends on ← depends_on_task_id
        // Meaning: task_id cannot start until depends_on_task_id is done.

        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
                   $table->foreignId('task_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('depends_on_task_id')
                  ->constrained('tasks')
                  ->cascadeOnDelete();

                  // Prevent duplicate edges in the graph
            $table->unique(
                ['task_id', 'depends_on_task_id'],
                'unique_task_dependency'
            );

            // Index for fast reverse lookup:
            // "Which tasks depend on task X?" — used in cycle detection DFS
            $table->index(
                ['depends_on_task_id'],
                'idx_depends_on_task'
            );
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');
    }
};
