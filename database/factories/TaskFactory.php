<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Model factories are Laravel's equivalent of a Doctrine/Foundry fixture
 * factory: fake-but-shaped data for tests and local seeding.
 *
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'completed' => fake()->boolean(30),
        ];
    }

    public function completed(): static
    {
        return $this->state(['completed' => true]);
    }
}
