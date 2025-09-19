<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class WarehouseReceiptController extends Controller
{
    // POST /api/warehouse/receipts
    public function store(Request $request)
    {
        $user = $request->user();
        $role = $user ? ($user->role ?? '') : null;
        if (! in_array($role, ['warehouse_manager','administrator'])) {
            return response()->json(['status'=>'error','message'=>'Access denied. Warehouse manager only.'], 403);
        }

        $v = Validator::make($request->all(), [
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'note' => 'nullable|string|max:1000',
        ]);

        if ($v->fails()) {
            return response()->json(['status'=>'validation_error','errors'=>$v->errors()], 422);
        }

        $data = $v->validated();

        DB::beginTransaction();
        try {
            $receiptId = DB::table('warehouse_receipts')->insertGetId([
                'warehouse_id' => $data['warehouse_id'],
                'user_id' => $user->id ?? null,
                'note' => $data['note'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['items'] as $it) {
                $productId = $it['product_id'];
                $qty = intval($it['quantity']);

                // upsert inventory row (warehouse_id, product_id)
                $inventory = DB::table('inventories')
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->where('product_id', $productId)
                    ->first();

                if ($inventory) {
                    DB::table('inventories')->where('id', $inventory->id)->increment('stock', $qty);
                    $inventoryId = $inventory->id;
                } else {
                    $inventoryId = DB::table('inventories')->insertGetId([
                        'warehouse_id' => $data['warehouse_id'],
                        'product_id' => $productId,
                        'stock' => $qty,
                        'minimum_stock' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('warehouse_receipt_items')->insert([
                    'receipt_id' => $receiptId,
                    'product_id' => $productId,
                    'quantity' => $qty,
                    'inventory_id' => $inventoryId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            return response()->json(['status'=>'ok','receipt_id'=>$receiptId], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }
}
