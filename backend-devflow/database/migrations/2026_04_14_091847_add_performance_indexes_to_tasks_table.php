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
        Schema::table('tasks', function (Blueprint $table) {
            // DSA — Binary Search (B-tree Index):
            // A composite index on (project_id, status) stores rows
            // sorted first by project_id then by status within each project.
            // A WHERE project_id=1 AND status='todo' query uses binary search
            // on this sorted structure — O(log n) — instead of reading all rows.
            //
            // Left-prefix rule: this index can also serve queries that filter
            // by project_id alone. It cannot serve status-only queries because
            // the sort order starts with project_id, not status.
            $table->index(['project_id', 'status'], 'idx_tasks_project_status');

            // DSA — Binary Search on sorted column:
            // ORDER BY due_date without an index forces MySQL to sort all
            // matching rows in memory — O(n log n).
            // With this index the rows are pre-sorted on disk — O(log n) seek
            // to the start position, then sequential read.
            $table->index(['due_date'], 'idx_tasks_due_date');

            // For user-scoped queries: WHERE created_by = X
            $table->index(['created_by', 'status'], 'idx_tasks_created_by_status');

            // Covering index: contains ALL columns the task list query needs.
            // MySQL reads everything from the index without touching the table.
            // This is the fastest possible read — no table lookup required.
            $table->index(
                ['project_id', 'status', 'priority', 'created_at'],
                'idx_tasks_covering'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_project_status');
            $table->dropIndex('idx_tasks_due_date');
            $table->dropIndex('idx_tasks_created_by_status');
            $table->dropIndex('idx_tasks_covering');
        });
    }
};
