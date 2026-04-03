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
        // User::factory(10)->create();

            $user = User::factory()->create([
            'name'     => 'Ali Khan',
            'email'    => 'ali@devflow.test',
            'password' => bcrypt('password'),
        ]);

        $projects = Project::factory(3)->create([
            'user_id' => $user->id,
        ]);

        $projects->each(function ($project) use ($user) {
            Task::factory(5)->create([
                'project_id' => $project->id,
                'created_by' => $user->id,
            ]);
        });
    }
}
