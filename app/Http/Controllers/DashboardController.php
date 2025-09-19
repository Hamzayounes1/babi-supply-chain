<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;

class DashboardController extends Controller
{
    protected function authorizeSCM(Request $request)
    {
        $user = $request->user();
        return $user && ($user->role ?? '') === 'supply_chain_manager';
    }

    public function global(Request $request)
    {
        if (! $this->authorizeSCM($request)) {
            return response()->json(['message' => 'Access denied. Supply Chain Manager only.'], 403);
        }

        // Total orders count
        $totalOrders = Order::count();

        // Total inventory (sum product quantity or inventories table stock)
        if (\Schema::hasTable('inventories')) {
            $totalInventory = \DB::table('inventories')->sum('stock');
        } else {
            $totalInventory = Product::sum('quantity');
        }

        // Supplier performance summary
        $supplierPerformance = Supplier::with(['orders'])->get()->map(function ($supplier) {
            $total = $supplier->orders->count();
            $delivered = $supplier->orders->where('delivered', true)->count();
            $onTime = $total ? round(($delivered / $total) * 100, 2) : 0;
            return [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'total_orders' => $total,
                'delivered_orders' => $delivered,
                'on_time_percentage' => $onTime,
            ];
        });

        return response()->json([
            'total_orders' => $totalOrders,
            'total_inventory' => $totalInventory,
            'supplier_performance' => $supplierPerformance,
        ]);
    }
}
