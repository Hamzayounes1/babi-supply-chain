<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku' => $this->faker->unique()->bothify('PROD-###'), // ✅ unique SKU
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->numberBetween(10, 500),
            'quantity' => $this->faker->numberBetween(1, 100),
        ];
    }
}
