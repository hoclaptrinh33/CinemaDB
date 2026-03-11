<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Title;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'user_id'           => User::factory(),
            'title_id'          => Title::factory(),
            'rating'            => fake()->numberBetween(1, 10),
            'review_text'       => fake()->paragraphs(2, true),
            'has_spoilers'      => false,
            'moderation_status' => 'VISIBLE',
            'reputation_earned' => 3,
        ];
    }

    /** Produce a review with no text (rating only). */
    public function ratingOnly(): static
    {
        return $this->state(fn(array $a) => [
            'review_text'       => null,
            'reputation_earned' => 2,
        ]);
    }

    /** Produce a review with ≥ 50 words (max reputation). */
    public function longText(): static
    {
        return $this->state(fn(array $a) => [
            'review_text'       => fake()->paragraphs(4, true),
            'reputation_earned' => 10,
        ]);
    }

    /** Mark review as hidden by a moderator. */
    public function hidden(): static
    {
        return $this->state(fn(array $a) => [
            'moderation_status' => 'HIDDEN',
        ]);
    }

    /** Mark review as containing spoilers. */
    public function withSpoilers(): static
    {
        return $this->state(fn(array $a) => [
            'has_spoilers' => true,
        ]);
    }
}
