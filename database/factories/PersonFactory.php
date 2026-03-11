<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        $gender = fake()->randomElement(['Male', 'Female', 'Other']);

        return [
            'full_name'    => fake()->name(),
            'birth_date'   => fake()->dateTimeBetween('-80 years', '-20 years')->format('Y-m-d'),
            'death_date'   => null,
            'gender'       => $gender,
            'country_id'   => Country::inRandomOrder()->value('country_id'),
            'biography'    => fake()->optional(0.7)->paragraphs(2, true),
            'profile_path' => null,
        ];
    }

    /** Mark person as deceased. */
    public function deceased(): static
    {
        return $this->state(fn(array $attributes) => [
            'death_date' => fake()->dateTimeBetween('-30 years', '-1 year')->format('Y-m-d'),
        ]);
    }
}
