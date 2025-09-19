<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Supplier;

class OrderSeeder extends Seeder {
    public function run() {
        $buyer = User::first(); // assumes at least one user
        $supplier = Supplier::first();
        if (!$buyer || !$supplier) return;

        $order = Order::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'Pending',
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'total' => 100,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Sample Widget',
            'product_sku' => 'SAMP-001',
            'quantity' => 10,
            'unit_price' => 10.0,
        ]);
    }
}
