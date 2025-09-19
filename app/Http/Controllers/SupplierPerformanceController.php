<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierPerformanceController extends Controller
{
    public function show($id)
    {
        $supplier = Supplier::with('orders')->findOrFail($id);

        $totalOrders = $supplier->orders->count();
        $delivered = $supplier->orders->where('delivered', true)->count();
        $onTimePercentage = $totalOrders ? round(($delivered / $totalOrders) * 100, 2) : 0;

        return response()->json([
            'supplier_id' => $supplier->id,
            'supplier_name' => $supplier->name,
            'total_orders' => $totalOrders,
            'delivered_orders' => $delivered,
            'on_time_percentage' => $onTimePercentage,
            'orders' => $supplier->orders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'product_id' => $order->product_id,
                    'quantity' => $order->quantity,
                    'order_date' => $order->order_date,
                    'delivered' => $order->delivered,
                    'delivery_date' => $order->delivery_date,
                ];
            }),
        ]);
    }
}
