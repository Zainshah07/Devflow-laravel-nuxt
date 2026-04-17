<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LargeDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding large dataset...');

        // Create 10 users
        $users = User::factory(10)->create();

        $this->command->info('Created 10 users');

        $taskRows = [];
        $now      = now()->toDateTimeString();

        foreach ($users as $user) {
            // 2 projects per user = 20 projects total
            $projects = Project::factory(2)->create(['user_id' => $user->id]);

            foreach ($projects as $project) {
                // 2500 tasks per project = 50,000 tasks total
                // DSA: bulk insert via DB::table()->insert() instead of
                // Eloquent::create() avoids instantiating 50,000 model
                // objects. Building the array first then inserting in
                // batches is O(n) time and O(batch_size) space —
                // same as chunked array processing in sliding window problems.
                for ($i = 0; $i < 2500; $i++) {
                    $taskRows[] = [
                        'project_id'  => $project->id,
                        'created_by'  => $user->id,
                        'title'       => fake()->sentence(4),
                        'description' => fake()->optional(0.7)->paragraph(),
                        'status'      => fake()->randomElement(TaskStatus::values()),
                        'priority'    => fake()->randomElement(TaskPriority::values()),
                        'due_date'    => fake()->optional(0.6)->dateTimeBetween('-30 days', '+60 days')?->format('Y-m-d'),
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ];

                    // Insert in batches of 500 to avoid memory exhaustion
                    // DSA: batch size 500 is a sliding window over the
                    // tasks array — process 500 at a time, slide forward.
                    if (count($taskRows) >= 500) {
                        DB::table('tasks')->insert($taskRows);
                        $taskRows = [];
                    }
                }
            }

            $this->command->info("Seeded tasks for user: {$user->email}");
        }

        // Insert any remaining rows
        if (!empty($taskRows)) {
            DB::table('tasks')->insert($taskRows);
        }

        $total = DB::table('tasks')->count();
        $this->command->info("Done. Total tasks in DB: {$total}");
    }
}