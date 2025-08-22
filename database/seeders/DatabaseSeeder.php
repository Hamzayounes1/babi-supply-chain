<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use Database\Seeders\WarehouseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run()
{
    // Other seeders if any...
    $this->call(WarehouseSeeder::class);
}
    /**
     * Seed the application's database.
     */
}

