<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

class InventoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(), // automatically create product if missing
            'stock' => $this->faker->numberBetween(5, 150),
            'minimum_stock' => $this->faker->numberBetween(5, 20),
        ];
    }
}
