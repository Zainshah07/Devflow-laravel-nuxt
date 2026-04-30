<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use App\Models\Task;



class Project extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'user_id'];

    public function owner(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function tasks(){
        return $this->hasMany(Task::class);
    }

    // DSA — Graph adjacency list:
    // members() returns all users with a membership edge to this project.
    // This includes the owner and all invited members.
    public function members()
    {
        return $this->belongsToMany(
            User::class,
            'project_members',
            'project_id',
            'user_id'
        )->withPivot('role')->withTimestamps();
    }

    // Check if a user is a member of this project (owner or invited)
    // DSA — Set membership check: O(1) with the unique index on
    // (project_id, user_id) in project_members table.
    public function hasMember(int $userId): bool
    {
        return $this->user_id === $userId
            || $this->members()->where('user_id', $userId)->exists();
    }
}
