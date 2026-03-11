<?php

namespace Database\Factories;

use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Badge>
 */
class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'slug'            => Str::slug($name) . '-' . fake()->randomNumber(4),
            'name'            => ucwords($name),
            'description'     => fake()->sentence(),
            'icon_path'       => null,
            'tier'            => fake()->randomElement(['BRONZE', 'SILVER', 'GOLD', 'PLATINUM']),
            'condition_type'  => 'review_count',
            'condition_value' => fake()->numberBetween(1, 100),
        ];
    }

    /** BRONZE tier badge. */
    public function bronze(): static
    {
        return $this->state(fn(array $a) => ['tier' => 'BRONZE']);
    }

    /** SILVER tier badge. */
    public function silver(): static
    {
        return $this->state(fn(array $a) => ['tier' => 'SILVER']);
    }

    /** GOLD tier badge. */
    public function gold(): static
    {
        return $this->state(fn(array $a) => ['tier' => 'GOLD']);
    }

    /** PLATINUM tier badge. */
    public function platinum(): static
    {
        return $this->state(fn(array $a) => ['tier' => 'PLATINUM']);
    }

    /** Manually awarded badge (no condition check). */
    public function manual(): static
    {
        return $this->state(fn(array $a) => [
            'condition_type'  => 'manual',
            'condition_value' => 0,
        ]);
    }

    /** Badge triggered by review count. */
    public function reviewCount(int $threshold): static
    {
        return $this->state(fn(array $a) => [
            'condition_type'  => 'review_count',
            'condition_value' => $threshold,
        ]);
    }

    /** Badge triggered by total helpful votes. */
    public function helpfulVotes(int $threshold): static
    {
        return $this->state(fn(array $a) => [
            'condition_type'  => 'helpful_votes',
            'condition_value' => $threshold,
        ]);
    }
}
