<?php

namespace Database\Factories;

use App\Enums\MatchStatus;
use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMatch>
 */
class GameMatchFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => null,
            'opponent' => fake()->company(),
            'venue' => 'Sân bóng Samba',
            'is_home' => true,
            'kickoff_at' => now()->addDays(3)->startOfHour(),
            'status' => MatchStatus::Scheduled,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MatchStatus::Completed,
            'kickoff_at' => now()->subDays(2)->startOfHour(),
            'score_for' => fake()->numberBetween(0, 8),
            'score_against' => fake()->numberBetween(0, 8),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MatchStatus::Cancelled,
        ]);
    }
}
