<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'code' => 'AUD-' . fake()->unique()->year() . '-' . str_pad(fake()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'name' => fake()->sentence(3),
            'auditee_agency' => fake()->company(),
            'period_start' => fake()->dateTimeBetween('-1 year', 'now'),
            'period_end' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => fake()->randomElement(['draft', 'review', 'final']),
            'created_by' => User::factory(),
        ];
    }
}
