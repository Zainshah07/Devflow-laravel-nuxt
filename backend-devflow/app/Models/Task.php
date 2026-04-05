<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Project;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;

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
}
