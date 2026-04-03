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
}
