<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
// In WarehouseSeeder.php

public function run()
{
    Warehouse::create(['name' => 'Central Depot', 'location' => 'Beirut']);
    Warehouse::create(['name' => 'North Hub', 'location' => 'Tripoli']);
}
}
