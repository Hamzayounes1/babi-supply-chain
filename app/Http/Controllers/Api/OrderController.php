<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller {

    // List orders (buyers see only their own; SCM/Admin see all; admins/scm may optionally filter by buyer_id)
    public function index(Request $request) {
        $user = $request->user();
        $query = Order::with(['items','supplier','buyer'])->orderBy('created_at','desc');

        // If the authenticated user is a buyer => force filter to their own orders
        if ($user && ($user->role ?? '') === 'buyer') {
            $query->where('buyer_id', $user->id);
        } else {
            // Admins/SCM: allow optional query param ?buyer_id=... to restrict results if desired
            if ($request->has('buyer_id')) {
                $query->where('buyer_id', $request->get('buyer_id'));
            }
        }

        $orders = $query->paginate(20);
        return response()->json($orders);
    }

    // show single order (buyers only their own)
    public function show(Request $request, $id) {
        $user = $request->user();
        $order = Order::with(['items','supplier','buyer'])->findOrFail($id);

        if ($user && ($user->role ?? '') === 'buyer' && $order->buyer_id !== $user->id) {
            return response()->json(['message'=>'Access denied. Buyer only.'], 403);
        }

        return response()->json(['status'=>'ok','data'=>$order]);
    }

    // store a new order with items
    public function store(Request $request) {
        $user = $request->user();
        // allow buyers (and admins) to create orders; buyers become the buyer_id automatically.
        $validated = $request->validate([
            'supplier_id' => ['nullable','exists:suppliers,id'],
            'order_date' => ['nullable','date'],
            'expected_date' => ['nullable','date'],
            'items' => ['required','array','min:1'],
            'items.*.product_name' => ['required','string'],
            'items.*.quantity' => ['required','integer','min:1'],
            'items.*.unit_price' => ['sometimes','nullable','numeric','min:0'],
            'items.*.product_sku' => ['sometimes','nullable','string'],
        ]);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'buyer_id' => $user ? $user->id : null,
                // supplier nullable (make sure DB allows nullable) - see migration below
                'supplier_id' => $validated['supplier_id'] ?? null,
                'status' => 'Pending',
                'order_date' => $validated['order_date'] ?? now(),
                'expected_date' => $validated['expected_date'] ?? null,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($validated['items'] as $it) {
                $unitPrice = isset($it['unit_price']) ? floatval($it['unit_price']) : 0.0;
                $lineTotal = $it['quantity'] * $unitPrice;
                $total += $lineTotal;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $it['product_name'],
                    'product_sku' => $it['product_sku'] ?? null,
                    'quantity' => $it['quantity'],
                    'unit_price' => $unitPrice,
                ]);
            }

            $order->total = $total;
            $order->save();

            DB::commit();
            return response()->json(['status'=>'ok','order'=>$order->load(['items','supplier','buyer'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }

    // update order (buyers can edit their own pending/requested orders; admin/SCM can edit all)
    public function update(Request $request, $id) {
        $user = $request->user();
        $order = Order::with('items')->findOrFail($id);

        // permission: buyers can only update their own orders
        if ($user && ($user->role ?? '') === 'buyer' && $order->buyer_id !== $user->id) {
            return response()->json(['message'=>'Access denied. Buyer only.'], 403);
        }

        // allow editing only for Pending or Requested (or let admin/scm bypass)
        $allowedStatusesForBuyerEdit = ['Pending','Requested'];
        if (($user && ($user->role ?? '') === 'buyer') && !in_array($order->status, $allowedStatusesForBuyerEdit)) {
            return response()->json(['status'=>'error','message'=>'Only pending/requested orders can be edited'], 400);
        }

        $validated = $request->validate([
            'supplier_id' => ['nullable','exists:suppliers,id'],
            'order_date' => ['nullable','date'],
            'expected_date' => ['nullable','date'],
            'items' => ['required','array','min:1'],
            'items.*.product_name' => ['required','string'],
            'items.*.quantity' => ['required','integer','min:1'],
            'items.*.unit_price' => ['sometimes','nullable','numeric','min:0'],
            'items.*.product_sku' => ['sometimes','nullable','string'],
        ]);

        DB::beginTransaction();
        try {
            $order->supplier_id = $validated['supplier_id'] ?? $order->supplier_id;
            $order->order_date = $validated['order_date'] ?? $order->order_date;
            $order->expected_date = $validated['expected_date'] ?? $order->expected_date;
            $order->save();

            // delete old items and insert new
            $order->items()->delete();
            $total = 0;
            foreach ($validated['items'] as $it) {
                $unitPrice = isset($it['unit_price']) ? floatval($it['unit_price']) : 0.0;
                $total += $it['quantity'] * $unitPrice;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $it['product_name'],
                    'product_sku' => $it['product_sku'] ?? null,
                    'quantity' => $it['quantity'],
                    'unit_price' => $unitPrice,
                ]);
            }
            $order->total = $total;
            $order->save();

            DB::commit();
            return response()->json(['status'=>'ok','order'=>$order->load(['items','supplier','buyer'])]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }

    // cancel order (buyers can cancel their own; admin/SCM can cancel any)
    public function cancel(Request $request, $id) {
        $user = $request->user();
        $order = Order::findOrFail($id);

        if ($user && ($user->role ?? '') === 'buyer' && $order->buyer_id !== $user->id) {
            return response()->json(['message'=>'Access denied. Buyer only.'], 403);
        }

        if ($order->status === 'Cancelled') {
            return response()->json(['status'=>'ok','message'=>'Already cancelled']);
        }

        $order->status = 'Cancelled';
        $order->save();
        return response()->json(['status'=>'ok','order'=>$order]);
    }

    // delete order (admin/SCM only recommended, but keep if needed)
    public function destroy(Request $request, $id) {
        $user = $request->user();
        if (! $user || !in_array(($user->role ?? ''), ['administrator','supply_chain_manager'])) {
            return response()->json(['message'=>'Access denied. Admin/SCM only.'], 403);
        }

        $order = Order::findOrFail($id);
        $order->delete();
        return response()->json(['status'=>'ok']);
    }

    // duplicate order (buyers can duplicate their own; admin/SCM can duplicate any)
    public function duplicate(Request $request, $id) {
        $user = $request->user();
        $source = Order::with('items')->findOrFail($id);

        if ($user && ($user->role ?? '') === 'buyer' && $source->buyer_id !== $user->id) {
            return response()->json(['message'=>'Access denied. Buyer only.'], 403);
        }

        DB::beginTransaction();
        try {
            $new = Order::create([
                'buyer_id' => $user ? $user->id : null,
                'supplier_id' => $source->supplier_id ?? null,
                'status' => 'Pending',
                'order_date' => now(),
                'expected_date' => $source->expected_date,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($source->items as $it) {
                $unitPrice = $it->unit_price ?? 0.0;
                OrderItem::create([
                    'order_id' => $new->id,
                    'product_name' => $it->product_name,
                    'product_sku' => $it->product_sku,
                    'quantity' => $it->quantity,
                    'unit_price' => $unitPrice,
                ]);
                $total += ($it->quantity * $unitPrice);
            }
            $new->total = $total;
            $new->save();

            DB::commit();
            return response()->json(['status'=>'ok','order'=>$new->load(['items','supplier','buyer'])], 201);
        } catch (\Exception $e){
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }
}
