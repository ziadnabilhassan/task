<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Task;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $faker = FakerFactory::create();

        return [
            'company_id' => Company::factory(),
            'assigned_to' => User::factory(),
            'title' => $faker->sentence(4),
            'description' => $faker->optional()->paragraph(),
            'status' => $faker->randomElement(Task::STATUSES),
            'priority' => $faker->randomElement(Task::PRIORITIES),
            'due_date' => $faker->optional()->dateTimeBetween('now', '+30 days'),
            'completed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Task::STATUS_PENDING,
            'completed_at' => null,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Task::STATUS_IN_PROGRESS,
            'completed_at' => null,
        ]);
    }

    public function completed(): static
    {
        $faker = FakerFactory::create();

        return $this->state(fn (array $attributes) => [
            'status' => Task::STATUS_COMPLETED,
            'completed_at' => $faker->dateTimeBetween('-30 days', 'now'),
        ]);
    }

    public function lowPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'low',
        ]);
    }

    public function mediumPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'medium',
        ]);
    }

    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }
}
