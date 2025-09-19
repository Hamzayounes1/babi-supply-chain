<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BuyerAlertsController extends Controller
{
    protected function authorizeBuyer(Request $request)
    {
        $user = $request->user();
        return $user && (($user->role ?? '') === 'buyer' || ($user->role ?? '') === 'administrator');
    }

    protected function denyResponse()
    {
        return response()->json(['message' => 'Access denied. Buyer only.'], 403);
    }

    public function index(Request $request)
    {
        if (! $this->authorizeBuyer($request)) return $this->denyResponse();
        $user = $request->user();

        // Late orders for this buyer
        $lateOrders = Order::where('buyer_id', $user->id)
            ->where('status', '!=', 'Delivered')
            ->where('order_date', '<', now()->subDays(7))
            ->get();

        // Low stock — similar approach to your SCM alerts but scoped to buyer's products (best-effort)
        $lowStock = [];
        if (\Schema::hasTable('inventories')) {
            $lowStock = DB::table('inventories')
                ->join('products', 'inventories.product_id', '=', 'products.id')
                ->whereColumn('inventories.stock', '<', 'inventories.minimum_stock')
                ->select('products.id as product_id', 'products.sku', 'products.name', 'inventories.stock', 'inventories.minimum_stock')
                ->get();
        } else {
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

    public function summary(Request $request)
    {
        if (! $this->authorizeBuyer($request)) return $this->denyResponse();
        $user = $request->user();
        $late = Order::where('buyer_id', $user->id)
            ->where('status', '!=', 'Delivered')
            ->where('order_date', '<', now()->subDays(7))->count();
        $open = Order::where('buyer_id', $user->id)->whereIn('status', ['Pending','Shipped'])->count();

        return response()->json([
            'late_orders' => $late,
            'open_orders' => $open,
        ]);
    }
}
