<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class AlertsController extends Controller
{
    protected function authorizeSCM(Request $request)
    {
        $user = $request->user();
        return $user && ($user->role ?? '') === 'supply_chain_manager';
    }

    public function index(Request $request)
    {
        if (! $this->authorizeSCM($request)) {
            return response()->json(['message' => 'Access denied. Supply Chain Manager only.'], 403);
        }

        // Late orders: not delivered and order_date older than 7 days
        $lateOrders = Order::where('delivered', false)
            ->where('order_date', '<', now()->subDays(7))
            ->get();

        // Low stock: use inventories.stock and minimum_stock if table exists, otherwise product.quantity vs product.minimum_stock
        $lowStock = [];
        if (\Schema::hasTable('inventories')) {
            $lowStock = DB::table('inventories')
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->whereColumn('inventories.stock', '<', 'inventories.minimum_stock')
                ->select('products.id as product_id', 'products.sku', 'products.name', 'inventories.stock', 'inventories.minimum_stock')
                ->get();
        } else {
            // fallback: require minimum_stock on products or fixed threshold 10
            $lowStock = Product::whereColumn('quantity', '<', 'minimum_stock')->get();
            if ($lowStock->isEmpty()) {
                $lowStock = Product::where('quantity', '<', 10)->get();
            }
        }

        return response()->json([
            'late_orders' => $lateOrders,
            'low_stock' => $lowStock,
        ]);
    }
}
