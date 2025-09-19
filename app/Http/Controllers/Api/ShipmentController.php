<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shipment; // assume you have a Shipment model; if not, create or adjust
use App\Models\ShipmentItem; // similarly
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ShipmentController extends Controller
{
    // list shipments (no auth middleware required; controller checks $request->user()->role)
    public function index(Request $request)
    {
        $query = Shipment::with(['items','warehouse'])->orderBy('created_at','desc');
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->get('warehouse_id'));
        }
        $list = $query->get();
        return response()->json(['status'=>'ok','data'=>$list]);
    }

    public function show($id)
    {
        $s = Shipment::with(['items','warehouse'])->find($id);
        if (! $s) return response()->json(['status'=>'error','message'=>'Not found'],404);
        return response()->json(['status'=>'ok','data'=>$s]);
    }

    // alias if client calls create()
    public function create(Request $request) {
        return $this->store($request);
    }

    // store a shipment (prepare/outbound) — only warehouse_manager, administrator, supply_chain_manager allowed
    public function store(Request $request)
    {
        $user = $request->user();

        $allowedRoles = ['warehouse_manager','administrator','supply_chain_manager'];
        if (! $user || ! in_array($user->role ?? '', $allowedRoles)) {
            return response()->json(['status'=>'forbidden','message'=>'Access denied. Warehouse manager only.'], 403);
        }

        $validated = $request->validate([
            'warehouse_id' => ['required','integer','exists:warehouses,id'],
            'destination' => ['nullable','string'],
            'items' => ['required','array','min:1'],
            'items.*.product_id' => ['required','integer','exists:products,id'],
            'items.*.quantity' => ['required','integer','min:1'],
            'items.*.unit_cost' => ['sometimes','nullable','numeric','min:0'],
        ]);

        DB::beginTransaction();
        try {
            $shipment = Shipment::create([
                'warehouse_id' => $validated['warehouse_id'],
                'destination' => $validated['destination'] ?? null,
                'prepared_by' => $user->id,
                'status' => 'Prepared',
                'total' => 0,
            ]);

            $total = 0;
            foreach ($validated['items'] as $it) {
                $qty = intval($it['quantity']);
                $unit = isset($it['unit_cost']) ? floatval($it['unit_cost']) : 0;
                $line = $qty * $unit;
                $total += $line;

                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'product_id' => $it['product_id'],
                    'quantity' => $qty,
                    'unit_cost' => $unit,
                ]);

                // reduce inventory if present
                $inventory = Inventory::where('warehouse_id', $validated['warehouse_id'])
                                       ->where('product_id', $it['product_id'])
                                       ->first();
                if ($inventory) {
                    $inventory->stock = max(0, intval($inventory->stock) - $qty);
                    $inventory->save();
                }
            }

            // store total if column exists
            if (Schema::hasColumn('shipments','total')) {
                $shipment->total = $total;
            } elseif (Schema::hasColumn('shipments','total_value')) {
                $shipment->total_value = $total;
            }
            $shipment->save();

            DB::commit();
            return response()->json(['status'=>'ok','data'=>$shipment->load('items')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }

    // cancel (optional)
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $allowedRoles = ['warehouse_manager','administrator','supply_chain_manager'];
        if (! $user || ! in_array($user->role ?? '', $allowedRoles)) {
            return response()->json(['status'=>'forbidden','message'=>'Access denied. Warehouse manager only.'], 403);
        }
        $s = Shipment::findOrFail($id);
        $s->status = 'Cancelled';
        $s->save();
        return response()->json(['status'=>'ok','data'=>$s]);
    }

    // duplicate (optional)
    public function duplicate(Request $request, $id)
    {
        $user = $request->user();
        $allowedRoles = ['warehouse_manager','administrator','supply_chain_manager'];
        if (! $user || ! in_array($user->role ?? '', $allowedRoles)) {
            return response()->json(['status'=>'forbidden','message'=>'Access denied. Warehouse manager only.'], 403);
        }
        $src = Shipment::with('items')->findOrFail($id);
        DB::beginTransaction();
        try {
            $new = Shipment::create([
                'warehouse_id' => $src->warehouse_id,
                'destination' => $src->destination,
                'prepared_by' => $user->id,
                'status' => 'Prepared',
                'total' => 0,
            ]);
            $total = 0;
            foreach ($src->items as $it) {
                $unit = $it->unit_cost ?? 0;
                ShipmentItem::create([
                    'shipment_id' => $new->id,
                    'product_id' => $it->product_id,
                    'quantity' => $it->quantity,
                    'unit_cost' => $unit,
                ]);
                $total += $it->quantity * $unit;
            }
            if (Schema::hasColumn('shipments','total')) $new->total = $total;
            elseif (Schema::hasColumn('shipments','total_value')) $new->total_value = $total;
            $new->save();
            DB::commit();
            return response()->json(['status'=>'ok','data'=>$new->load('items')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }
}
