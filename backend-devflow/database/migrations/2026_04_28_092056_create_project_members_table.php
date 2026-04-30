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
        Schema::create('project_members', function (Blueprint $table) {
           // DSA — Graph edge table:
        // Each row is a directed edge: User → Project (member of)
        // user_id and project_id together form the adjacency list
        // for the "membership" relationship.
        
            $table->id();
            $table->foreignId('project_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->enum('role', ['owner', 'member', 'viewer'])
                  ->default('member');
           

            // Prevent duplicate membership edges
            $table->unique(['project_id', 'user_id'], 'unique_project_member');

            // Index for fast "what projects does this user belong to" queries
            $table->index(['user_id', 'project_id'], 'idx_project_members_user');
             $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
