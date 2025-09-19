<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouse;
use App\Models\Inventory;
use Illuminate\Support\Facades\Validator;

class WarehouseInventoryController extends Controller
{
    protected function authorizeWarehouseManager(Request $request)
    {
        $user = $request->user();
        // Allow admin too (optional). Adjust if you want admin only via other checks.
        return $user && in_array(($user->role ?? ''), ['warehouse_manager','administrator']);
    }

    protected function deny()
    {
        return response()->json(['message'=>'Access denied. Warehouse manager only.'], 403);
    }

    // GET /warehouses
    public function index(Request $request)
    {
        if (! $this->authorizeWarehouseManager($request)) return $this->deny();
        $warehouses = Warehouse::orderBy('name')->get();
        return response()->json($warehouses);
    }

    // GET /warehouses/{id}/inventories  => returns inventory rows with product info and location
    public function inventories(Request $request, $warehouseId)
    {
        if (! $this->authorizeWarehouseManager($request)) return $this->deny();

        $w = Warehouse::find($warehouseId);
        if (! $w) return response()->json(['message'=>'Warehouse not found'], 404);

        $inventories = Inventory::with('product')
            ->where('warehouse_id', $warehouseId)
            ->orderBy('location')
            ->get();

        return response()->json(['warehouse'=>$w, 'inventories'=>$inventories]);
    }

    // PUT /inventories/{id}/stock  — update numeric stock/quantity
    public function updateStock(Request $request, $inventoryId)
    {
        if (! $this->authorizeWarehouseManager($request)) return $this->deny();

        $inv = Inventory::find($inventoryId);
        if (! $inv) return response()->json(['message'=>'Inventory row not found'], 404);

        $v = Validator::make($request->all(), [
            'stock' => 'required|integer|min:0' // change to 'quantity' if that's your column
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation failed','errors'=>$v->errors()], 422);

        $inv->stock = (int) $request->get('stock'); // or ->quantity
        $inv->save();

        return response()->json(['status'=>'ok','inventory'=>$inv]);
    }

    // PUT /inventories/{id}/location  — update location string
    public function updateLocation(Request $request, $inventoryId)
    {
        if (! $this->authorizeWarehouseManager($request)) return $this->deny();

        $inv = Inventory::find($inventoryId);
        if (! $inv) return response()->json(['message'=>'Inventory row not found'], 404);

        $v = Validator::make($request->all(), [
            'location' => 'nullable|string|max:64'
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation failed','errors'=>$v->errors()], 422);

        $inv->location = $request->get('location');
        $inv->save();

        return response()->json(['status'=>'ok','inventory'=>$inv]);
    }

    // Optional: assign inventory row to warehouse (POST /warehouses/{id}/assign-inventory)
    public function assignInventoryToWarehouse(Request $request, $warehouseId)
    {
        if (! $this->authorizeWarehouseManager($request)) return $this->deny();

        $w = Warehouse::find($warehouseId);
        if (! $w) return response()->json(['message'=>'Warehouse not found'], 404);

        $v = Validator::make($request->all(), [
            'inventory_id' => 'required|integer|exists:inventories,id',
            'location' => 'nullable|string|max:64',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation failed','errors'=>$v->errors()], 422);

        $inv = Inventory::find($request->get('inventory_id'));
        $inv->warehouse_id = $warehouseId;
        $inv->location = $request->get('location') ?? $inv->location;
        $inv->save();

        return response()->json(['status'=>'ok','inventory'=>$inv]);
    }
}
