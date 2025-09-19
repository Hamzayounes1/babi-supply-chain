<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        $product = Product::inRandomOrder()->first() ?? Product::factory()->create();
        $supplier = Supplier::inRandomOrder()->first() ?? Supplier::factory()->create();
        $buyer = User::where('role', 'buyer')->inRandomOrder()->first();

        $quantity = $this->faker->numberBetween(1, 20);
        $orderDate = Carbon::now()->subDays(rand(1, 30));
        $delivered = $this->faker->boolean();

        return [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'buyer_id' => $buyer ? $buyer->id : User::factory()->create(['role' => 'buyer'])->id,
            'quantity' => $quantity,
            'order_date' => $orderDate,
            'status' => $delivered ? 'delivered' : 'pending',
            // 'delivered' => $delivered,
            'delivery_date' => $delivered ? $orderDate->copy()->addDays(rand(1, 5)) : null,
            'total' => $product->price * $quantity,
        ];
    }
}
