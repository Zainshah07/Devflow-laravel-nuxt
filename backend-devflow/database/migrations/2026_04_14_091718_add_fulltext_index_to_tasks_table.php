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
        // DSA — Hash Map (Inverted Index):
        // MySQL FULLTEXT creates an inverted index internally.
        // Every unique word across title and description becomes a key.
        // The value is the list of task IDs containing that word.
        // Searching for "login bug" is two O(1) hash map lookups
        // followed by a set intersection — exactly like the
        // frequency map pattern in Two Sum / Group Anagrams.
       Schema::table('tasks', function (Blueprint $table) {
            $table->fullText(['title', 'description'], 'tasks_fulltext_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropFullText('tasks_fulltext_search');
        });
    }
};
