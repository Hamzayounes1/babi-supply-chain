<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Shipment;
use App\Models\Inventory;

class WarehouseShipmentController extends Controller
{
    // List shipments
    public function index(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user || ! in_array($user->role, ['warehouse_manager', 'administrator'])) {
            return response()->json(['message' => 'Access denied. Warehouse manager only.'], 403);
        }

        $shipments = Shipment::with('items')->get();
        return response()->json($shipments);
    }

    // Create new shipment
    public function create(Request $request)
    {
        $user = User::find($request->user_id);

        if (! $user || ! in_array($user->role, ['warehouse_manager', 'administrator'])) {
            return response()->json(['message' => 'Access denied. Warehouse manager only.'], 403);
        }

        $validated = $request->validate([
            'warehouse_id' => 'required|integer',
            'destination' => 'required|string',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        $shipment = new Shipment();
        $shipment->warehouse_id = $validated['warehouse_id'];
        $shipment->destination = $validated['destination'];
        $shipment->status = 'prepared';
        $shipment->save();

        $totalValue = 0;
        foreach ($validated['items'] as $item) {
            $shipment->items()->create($item);

            // decrease stock
            $inventory = Inventory::where('warehouse_id', $validated['warehouse_id'])
                ->where('product_id', $item['product_id'])
                ->first();

            if ($inventory) {
                $inventory->stock -= $item['quantity'];
                $inventory->save();
            }

            $totalValue += ($item['unit_cost'] ?? 0) * $item['quantity'];
        }

        $shipment->total_value = $totalValue;
        $shipment->save();

        return response()->json($shipment, 201);
    }

    // Mark as sent
    public function markAsSent(Request $request, $id)
    {
        $user = User::find($request->user_id);

        if (! $user || ! in_array($user->role, ['warehouse_manager', 'administrator'])) {
            return response()->json(['message' => 'Access denied. Warehouse manager only.'], 403);
        }

        $shipment = Shipment::findOrFail($id);
        $shipment->status = 'sent';
        $shipment->save();

        return response()->json(['message' => 'Shipment marked as sent']);
    }
    public function warehouses() {
    $warehouses = \App\Models\Warehouse::all(['id', 'name']);
    return response()->json(['data' => $warehouses]);
}

public function products() {
    $products = \App\Models\Product::all(['id', 'name']);
    return response()->json(['data' => $products]);
}

}
