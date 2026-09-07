<?php

namespace Database\Factories;

use App\Enums\MemberRole;
use App\Enums\MemberStatus;
use App\Enums\PitchSlot;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nickname' => fake()->optional()->firstName(),
            'jersey_number' => fake()->optional()->numberBetween(1, 99),
            'preferred_position' => fake()->randomElement(PitchSlot::starters())->value,
            'phone' => fake()->optional()->numerify('09########'),
            'date_of_birth' => fake()->optional()->date(),
            'joined_at' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'status' => MemberStatus::Active,
            'role' => MemberRole::Player,
            'bio' => fake()->optional()->sentence(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MemberStatus::Inactive,
        ]);
    }
}
