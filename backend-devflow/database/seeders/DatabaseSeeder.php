<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
{
    $userA = User::updateOrCreate([
        'name'     => 'Ali Khan',
        'email'    => 'ali@devflow.test',
        'password' => bcrypt('password'),
    ]);

    $userB = User::updateOrCreate([
        'name'     => 'Sara Raza',
        'email'    => 'sara@devflow.test',
        'password' => bcrypt('password'),
    ]);

    $projects = Project::factory(3)->create([
        'user_id' => $userA->id,
    ]);

    // Add userB as a member of the first project
    // Now both users can view the same project and appear in presence bar
    $projects->first()->members()->attach($userB->id, ['role' => 'member']);

    $projects->each(function ($project) use ($userA) {
        Task::factory(5)->create([
            'project_id' => $project->id,
            'created_by' => $userA->id,
        ]);
    });
}
}
