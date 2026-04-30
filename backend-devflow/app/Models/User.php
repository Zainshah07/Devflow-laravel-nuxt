<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Project;
use App\Models\RefreshToken;
use App\Models\Task;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;
    // protected $connection = 'mysql';
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projects(){
        return $this->hasMany(Project::class);
    }
    public function assignedTasks(){
        return $this->belongsToMany(
            Task::class,
            'task_assignments',
            'user_id',
            'task_id'
        )->withPivot('assigned_at');
    }

    public function refreshTokens(){
        return $this->hasMany(RefreshToken::class);
    }

    public function memberProjects(): BelongsToMany
{
    return $this->belongsToMany(
        Project::class,
        'project_members',
        'user_id',
        'project_id'
    )->withPivot('role')->withTimestamps();
}

// All projects accessible to this user (owned + member of)
// DSA — Set union: combines two disjoint sets of project IDs
// into a single collection with no duplicates.
public function accessibleProjects(): \Illuminate\Database\Eloquent\Builder
{
    return Project::where('user_id', $this->id)
        ->orWhereHas('members', fn ($q) => $q->where('user_id', $this->id));
}
    
}
