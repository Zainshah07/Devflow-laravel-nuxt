<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Project;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Builder;

class Task extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'description', 'status', 'priority', 'due_date', 'project_id', 'created_by'];

    protected $casts = [
        'status'=>TaskStatus::class,
        'priority'=>TaskPriority::class,
        'due_date' => 'date',
    ];

    public function project(){
        return $this->belongsTo(Project::class, 'project_id');
    }


    public function creator(){
        return $this->belongsTo(User::class, 'created_by');
    }


    public function assignees(){
          return $this->belongsToMany(
            User::class,
            'task_assignments',
            'task_id',
            'user_id'
        )->withPivot('assigned_at')->withTimestamps();
    }

    public function isOverdue():bool
    {
         return $this->due_date !== null
            && $this->due_date->isPast()
            && $this->status !== TaskStatus::Done;
    }

    // ─────────────────────────────────────────────────────────────────
    // DSA — Hash Map (Inverted Index search):
    // whereFullText generates a MySQL MATCH AGAINST query.
    // MySQL looks up each word in the search term in the inverted
    // index hash map — O(1) per word — then intersects the result
    // sets. Far faster than LIKE '%term%' which forces a full scan.
    // ─────────────────────────────────────────────────────────────────
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->whereFullText(['title', 'description'], $term);
    }

    // DSA — Array filter predicate:
    // scopeOverdue is a reusable WHERE clause that filters the
    // "array" (table rows) by two conditions: due_date in the past
    // and status not done. Uses the idx_tasks_due_date index.
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->where('status', '!=', TaskStatus::Done->value);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->where('priority', TaskPriority::High->value);
    }

     public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }
}
