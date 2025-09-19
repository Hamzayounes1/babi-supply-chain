<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'contact_email' => $this->faker->companyEmail(),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),

            // extra columns you mentioned
            'rating' => $this->faker->randomFloat(1, 1, 5), // 1.0 – 5.0
            'performance_score' => $this->faker->numberBetween(70, 100),
            'on_time_percentage' => $this->faker->numberBetween(70, 100),
        ];
    }
}
