<?php

namespace Database\Factories;

use App\Enums\FundStatus;
use App\Models\FundContribution;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundContribution>
 */
class FundContributionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'title' => 'Quỹ tháng '.now()->format('m/Y'),
            'amount' => 100000,
            'status' => FundStatus::Unpaid,
            'group_key' => fake()->uuid(),
            'note' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => FundStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
