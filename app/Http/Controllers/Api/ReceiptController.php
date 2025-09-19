<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Receipt;       // create model if not exist
use App\Models\ReceiptItem;   // create model if not exist
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReceiptController extends Controller
{
    // GET /api/receipts
    public function index(Request $request)
    {
        // optional filtering by warehouse
        $query = Receipt::with(['items','warehouse','supplier'])->orderBy('created_at','desc');
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->get('warehouse_id'));
        }
        $list = $query->get();
        return response()->json(['status'=>'ok','data'=>$list], 200);
    }

    // GET /api/receipts/{id}
    public function show($id)
    {
        $rec = Receipt::with(['items','warehouse','supplier'])->find($id);
        if (! $rec) return response()->json(['status'=>'error','message'=>'Receipt not found'], 404);
        return response()->json(['status'=>'ok','data'=>$rec], 200);
    }

    // POST /api/receipts
    public function store(Request $request)
    {
        // user role check using users.role column
        $user = $request->user();
        if (!$user) {
            return response()->json(['status'=>'error','message'=>'Not authenticated'], 401);
        }
        $role = $user->role ?? '';
        if (! in_array($role, ['warehouse_manager','administrator','logistics','supply_chain_manager'])) {
            return response()->json(['status'=>'error','message'=>'Access denied. Warehouse manager only.'], 403);
        }

        $v = Validator::make($request->all(), [
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'supplier_id'  => 'nullable|integer|exists:suppliers,id',
            'reference'    => 'nullable|string|max:255',
            'items'        => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_cost'  => 'nullable|numeric|min:0',
        ]);
        if ($v->fails()) {
            return response()->json(['status'=>'validation_error','errors'=>$v->errors()], 422);
        }

        $data = $v->validated();

        DB::beginTransaction();
        try {
            $receipt = Receipt::create([
                'warehouse_id' => $data['warehouse_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'reference' => $data['reference'] ?? null,
                'total_value' => 0,
                'created_by' => $user->id,
            ]);

            $total = 0;
            foreach ($data['items'] as $it) {
                $qty = intval($it['quantity']);
                $unit = isset($it['unit_cost']) ? floatval($it['unit_cost']) : 0;
                $line = ReceiptItem::create([
                    'receipt_id' => $receipt->id,
                    'product_id' => $it['product_id'],
                    'quantity'   => $qty,
                    'unit_cost'  => $unit,
                ]);
                $total += $qty * $unit;

                // update or create inventory row for warehouse+product
                $inv = Inventory::firstOrCreate(
                  ['warehouse_id' => $data['warehouse_id'], 'product_id' => $it['product_id']],
                  ['stock' => 0, 'minimum_stock' => 0]
                );
                $inv->stock = intval($inv->stock) + $qty;
                $inv->save();
            }

            $receipt->total_value = $total;
            $receipt->save();

            DB::commit();
            return response()->json(['status'=>'ok','data'=>$receipt->load('items','warehouse','supplier')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }
}
