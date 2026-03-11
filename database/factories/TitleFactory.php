<?php

namespace Database\Factories;

use App\Models\Language;
use App\Models\Title;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Title>
 */
class TitleFactory extends Factory
{
    protected $model = Title::class;

    public function definition(): array
    {
        return [
            'title_name'           => fake()->unique()->catchPhrase(),
            'original_language_id' => Language::inRandomOrder()->value('language_id'),
            'release_date'         => fake()->dateTimeBetween('-30 years', 'now')->format('Y-m-d'),
            'runtime_mins'         => fake()->numberBetween(70, 180),
            'title_type'           => 'MOVIE',
            'description'          => fake()->paragraphs(3, true),
            'poster_path'          => null,
            'backdrop_path'        => null,
            'trailer_url'          => null,
            'status'               => fake()->randomElement(['Released', 'Post Production', 'Rumored', 'Canceled']),
            'budget'               => fake()->optional(0.7)->numberBetween(1_000_000, 300_000_000),
            'revenue'              => fake()->optional(0.6)->numberBetween(500_000, 1_000_000_000),
            'visibility'           => 'PUBLIC',
        ];
    }

    /** Override to produce a SERIES title. */
    public function series(): static
    {
        return $this->state(fn(array $attributes) => [
            'title_type'   => 'SERIES',
            'runtime_mins' => null,
        ]);
    }

    /** Override to produce an EPISODE title. */
    public function episode(): static
    {
        return $this->state(fn(array $attributes) => [
            'title_type'   => 'EPISODE',
            'runtime_mins' => fake()->numberBetween(20, 60),
        ]);
    }

    /** Mark as released. */
    public function released(): static
    {
        return $this->state(fn(array $attributes) => [
            'status'     => 'Released',
            'visibility' => 'PUBLIC',
        ]);
    }
}
