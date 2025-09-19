<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\Order;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // 2. Suppliers
        // Supplier::factory()
        //     ->count(50)
        //     ->create();

        // 3. Products + Inventory
        // Product::factory()
        //     ->count(500)
        //     ->create()
        //     ->each(function ($product) {
        //         Inventory::factory()
        //             ->create([
        //                 'product_id' => $product->id,
        //             ]);
        //     });

        // 4. Orders
        //    If your Order factory attaches items via a relationship/factory, 
        //    that will run automatically.
        Order::factory()
            ->count(1000)
            ->create();
    }
}