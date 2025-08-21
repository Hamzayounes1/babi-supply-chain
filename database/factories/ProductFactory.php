<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Product;

class ProductFactory extends Factory
{
    // The name of the factory’s corresponding model
    protected $model = Product::class;

    public function definition()
    {
        return [
            // Unique SKU: 8 uppercase alphanumeric characters
            'sku' => strtoupper(Str::random(8)),

            // Product name as a combination of 2 faker words
            'name' => $this->faker->words(2, true),

            // Optional description up to 200 chars
            'description' => $this->faker->optional()->text(200),

            // Price between 10.00 and 1000.00
            'price' => $this->faker->randomFloat(2, 10, 1000),

            // created_at and updated_at are auto‐handled by Eloquent
        ];
    }
}