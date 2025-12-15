<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Neighborhood;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Neighborhood>
 */
class NeighborhoodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->streetName(),
            'city_id' => City::factory(),
            'state_id' => State::factory(),
            'country_id' => Country::factory(),
            'zip_code' => $this->faker->postcode(),
            'latitude' => $this->faker->latitude(-33, 5),
            'longitude' => $this->faker->longitude(-73, -34),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the neighborhood is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
