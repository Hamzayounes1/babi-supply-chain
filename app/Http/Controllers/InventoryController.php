<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * GET /api/inventories or GET /api/inventories?warehouse_id=&product_id=
     * Returns list of inventories with product (+ warehouse) relation.
     */
    public function index(Request $request)
    {
        $query = Inventory::with(['product', 'warehouse']);

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->get('warehouse_id'));
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->get('product_id'));
        }

        // optional pagination support via ?page=
        $perPage = intval($request->get('per_page', 100));
        if ($perPage > 0 && $request->has('page')) {
            $items = $query->orderBy('id', 'desc')->paginate($perPage);
        } else {
            $items = $query->orderBy('id', 'desc')->get();
        }

        return response()->json(['status' => 'ok', 'data' => $items]);
    }

    /**
     * GET /api/inventories/{id}
     */
    public function show($id)
    {
        $inv = Inventory::with(['product', 'warehouse'])->find($id);
        if (! $inv) {
            return response()->json(['status' => 'error', 'message' => 'Inventory not found'], 404);
        }
        return response()->json(['status' => 'ok', 'data' => $inv]);
    }

    /**
     * POST /api/inventories
     * Create a new inventory record (admin / SCM only).
     */
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role ?? '', ['administrator', 'supply_chain_manager'])) {
            return response()->json(['status' => 'forbidden', 'message' => 'Access denied. Administrator or Supply Chain Manager only.'], 403);
        }

        $v = Validator::make($request->all(), [
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'product_id' => 'required|integer|exists:products,id',
            'stock' => 'required|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'location_aisle' => 'nullable|string|max:64',
            'location_row' => 'nullable|string|max:64',
            'location_shelf' => 'nullable|string|max:64',
            'location_bin' => 'nullable|string|max:64',
            'location_label' => 'nullable|string|max:255',
        ]);

        if ($v->fails()) {
            return response()->json(['status' => 'validation_error', 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();

        $inventory = Inventory::create([
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'product_id' => $data['product_id'],
            'stock' => $data['stock'],
            'minimum_stock' => $data['minimum_stock'] ?? 0,
            'location_aisle' => $data['location_aisle'] ?? null,
            'location_row' => $data['location_row'] ?? null,
            'location_shelf' => $data['location_shelf'] ?? null,
            'location_bin' => $data['location_bin'] ?? null,
            'location_label' => $data['location_label'] ?? null,
        ]);

        return response()->json(['status' => 'ok', 'data' => $inventory], 201);
    }

    /**
     * PATCH/PUT /api/inventories/{id}
     * Edit inventory record (admin / SCM).
     */
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role ?? '', ['administrator', 'supply_chain_manager'])) {
            return response()->json(['status' => 'forbidden', 'message' => 'Access denied. Administrator or Supply Chain Manager only.'], 403);
        }

        $inventory = Inventory::find($id);
        if (! $inventory) {
            return response()->json(['status' => 'error', 'message' => 'Inventory not found'], 404);
        }

        $v = Validator::make($request->all(), [
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'stock' => 'nullable|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'location_aisle' => 'nullable|string|max:64',
            'location_row' => 'nullable|string|max:64',
            'location_shelf' => 'nullable|string|max:64',
            'location_bin' => 'nullable|string|max:64',
            'location_label' => 'nullable|string|max:255',
        ]);

        if ($v->fails()) {
            return response()->json(['status' => 'validation_error', 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();

        $inventory->warehouse_id = $data['warehouse_id'] ?? $inventory->warehouse_id;
        $inventory->product_id = $data['product_id'] ?? $inventory->product_id;
        if (isset($data['stock'])) $inventory->stock = $data['stock'];
        if (isset($data['minimum_stock'])) $inventory->minimum_stock = $data['minimum_stock'];
        if (isset($data['location_aisle'])) $inventory->location_aisle = $data['location_aisle'];
        if (isset($data['location_row'])) $inventory->location_row = $data['location_row'];
        if (isset($data['location_shelf'])) $inventory->location_shelf = $data['location_shelf'];
        if (isset($data['location_bin'])) $inventory->location_bin = $data['location_bin'];
        if (isset($data['location_label'])) $inventory->location_label = $data['location_label'];

        $inventory->save();

        return response()->json(['status' => 'ok', 'data' => $inventory]);
    }

    /**
     * DELETE /api/inventories/{id}
     * Delete inventory record (admin/SCM).
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (! $user || ! in_array($user->role ?? '', ['administrator', 'supply_chain_manager','warehouse_manager'])) {
            return response()->json(['status' => 'forbidden', 'message' => 'Access denied. Administrator or Supply Chain Manager only.'], 403);
        }

        $inventory = Inventory::find($id);
        if (! $inventory) {
            return response()->json(['status' => 'error', 'message' => 'Inventory not found'], 404);
        }

        $inventory->delete();
        return response()->json(['status' => 'ok', 'message' => 'Inventory deleted']);
    }
    public function warehouses()
{
    // Optional: if you have a Warehouse model
    try {
        $warehouses = \App\Models\Warehouse::all();
        return response()->json([
            'status' => 'ok',
            'data' => $warehouses
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}
public function products()
{
    try {
        $products = \App\Models\Product::all();
        return response()->json([
            'status' => 'ok',
            'data' => $products
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
}


    /**
     * POST /api/inventory/update
     *
     * Body can contain:
     *  - inventory_id (preferred)
     * OR
     *  - warehouse_id + product_id
     * And one of:
     *  - stock (absolute integer)
     *  - adjustment (integer delta, can be negative)
     *
     * Also accepts structured location fields:
     *  - location_aisle, location_row, location_shelf, location_bin, location_label
     *
     * Allowed roles: warehouse_manager, administrator, supply_chain_manager
     */
    public function updateStock(Request $request)
    {
        $user = $request->user();

        // Role check: allow warehouse_manager, admin and supply_chain_manager
        $allowed = ($user && in_array($user->role ?? '', ['warehouse_manager', 'administrator', 'supply_chain_manager','wearhouse_manager','logistics']));
        if (! $allowed) {
            return response()->json(['status' => 'forbidden', 'message' => 'Access denied. Warehouse manager only.'], 403);
        }

        $v = Validator::make($request->all(), [
            'inventory_id' => 'nullable|integer|exists:inventories,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'product_id' => 'nullable|integer|exists:products,id',
            'stock' => 'nullable|integer|min:0',
            'adjustment' => 'nullable|integer',
            'location_aisle' => 'nullable|string|max:64',
            'location_row' => 'nullable|string|max:64',
            'location_shelf' => 'nullable|string|max:64',
            'location_bin' => 'nullable|string|max:64',
            'location_label' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:1024',
        ]);

        if ($v->fails()) {
            return response()->json(['status' => 'validation_error', 'errors' => $v->errors()], 422);
        }

        $data = $v->validated();

        // Must specify target
        if (empty($data['inventory_id']) && (empty($data['warehouse_id']) || empty($data['product_id']))) {
            return response()->json(['status' => 'error', 'message' => 'Provide inventory_id OR (warehouse_id + product_id)'], 422);
        }

        DB::beginTransaction();
        try {
            if (! empty($data['inventory_id'])) {
                $inventory = Inventory::find($data['inventory_id']);
                if (! $inventory) {
                    DB::rollBack();
                    return response()->json(['status' => 'error', 'message' => 'Inventory not found'], 404);
                }
            } else {
                $inventory = Inventory::firstOrCreate([
                    'warehouse_id' => $data['warehouse_id'],
                    'product_id' => $data['product_id'],
                ], [
                    'stock' => 0,
                    'minimum_stock' => 0,
                ]);
            }

            // Calculate new stock (use 'stock' and 'adjustment' — not 'quantity')
            if (isset($data['stock'])) {
                $inventory->stock = (int) $data['stock'];
                $action = 'set';
            } elseif (isset($data['adjustment'])) {
                $inventory->stock = intval($inventory->stock) + intval($data['adjustment']);
                $action = 'adjust';
            } else {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Provide stock or adjustment'], 422);
            }

            // Prevent negative
            if ($inventory->stock < 0) $inventory->stock = 0;

            // Structured location fields (preferred)
            if (isset($data['location_aisle'])) $inventory->location_aisle = $data['location_aisle'];
            if (isset($data['location_row'])) $inventory->location_row = $data['location_row'];
            if (isset($data['location_shelf'])) $inventory->location_shelf = $data['location_shelf'];
            if (isset($data['location_bin'])) $inventory->location_bin = $data['location_bin'];

            // label (or accept legacy single 'location' if present)
            if (isset($data['location_label'])) {
                $inventory->location_label = $data['location_label'];
            } elseif (isset($data['location'])) {
                $inventory->location_label = $data['location'];
            }

            $inventory->save();

            // Optionally record audit log here (not implemented) — could insert into inventory_changes table

            DB::commit();
            return response()->json(['status' => 'ok', 'action' => $action, 'data' => $inventory]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
