<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Carbon\Carbon;

class InventoryForecastController extends Controller
{
    public function inventory(Request $request)
    {
        $user = $request->user();
        if (! $user || ($user->role ?? '') !== 'supply_chain_manager') {
            return response()->json(['message' => 'Access Denied'], 403);
        }

        try {
            $required = ['products', 'inventories', 'orders'];
            $missing = [];
            foreach ($required as $t) {
                if (! Schema::hasTable($t)) $missing[] = $t;
            }
            if (! empty($missing)) {
                return response()->json([
                    'message' => 'Required tables missing: ' . implode(', ', $missing)
                ], 500);
            }

            $daysWindow = 90;
            $since = Carbon::now()->subDays($daysWindow)->toDateString();

            $rows = DB::table('products')
                ->leftJoin('inventories', 'products.id', '=', 'inventories.product_id')
                ->select(
                    'products.id as product_id',
                    'products.sku',
                    'products.name as product_name',
                    DB::raw('COALESCE(inventories.stock,0) as current_stock'),
                    DB::raw('COALESCE(inventories.minimum_stock,0) as minimum_stock')
                )
                ->get();

            $forecasts = [];
            foreach ($rows as $r) {
                $ordersSum = DB::table('orders')
                    ->where('product_id', $r->product_id)
                    ->where('order_date', '>=', $since)
                    ->sum('quantity');

                $avgDaily = $ordersSum / max(1, $daysWindow);
                $forecastNextMonth = (int) ceil($avgDaily * 30);

                $forecasts[] = [
                    'product_id' => $r->product_id,
                    'sku' => $r->sku,
                    'product_name' => $r->product_name,
                    'current_stock' => (int)$r->current_stock,
                    'minimum_stock' => (int)$r->minimum_stock,
                    'forecast_next_month' => $forecastNextMonth,
                    'needs_restock' => $r->current_stock < ($forecastNextMonth + $r->minimum_stock)
                ];
            }

            return response()->json(['data' => $forecasts], 200);

        } catch (QueryException $ex) {
            return response()->json(['message' => 'DB error: ' . $ex->getMessage()], 500);
        } catch (\Exception $ex) {
            return response()->json(['message' => 'Server error: ' . $ex->getMessage()], 500);
        }
    }
}
