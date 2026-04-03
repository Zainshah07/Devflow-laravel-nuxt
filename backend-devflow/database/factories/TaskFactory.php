<?php

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Project;
use App\Models\User;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id'  => Project::factory(),
            'created_by'  => User::factory(),
            'title'       => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'status'      => $this->faker->randomElement(TaskStatus::values()),
            'priority'    => $this->faker->randomElement(TaskPriority::values()),
            'due_date'    => $this->faker->optional()->dateTimeBetween('now', '+60 days'),
        ];
    }
}
