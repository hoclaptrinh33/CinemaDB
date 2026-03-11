<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    protected $model = Collection::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'name'        => fake()->unique()->words(3, true),
            'description' => fake()->optional(0.7)->sentence(),
            'visibility'  => 'PRIVATE',
            'is_published' => false,
        ];
    }

    /** Make the collection public. */
    public function public(): static
    {
        return $this->state(fn(array $a) => ['visibility' => 'PUBLIC']);
    }

    /** Make the collection published (requires public). */
    public function published(): static
    {
        return $this->state(fn(array $a) => [
            'visibility'      => 'PUBLIC',
            'is_published'    => true,
            'published_at'    => now(),
            'publish_headline' => fake()->sentence(),
            'publish_body'    => fake()->paragraph(),
        ]);
    }

    /** Make the collection private. */
    public function private(): static
    {
        return $this->state(fn(array $a) => ['visibility' => 'PRIVATE']);
    }
}
