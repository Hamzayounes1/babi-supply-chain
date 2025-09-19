<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BuyerOrderController extends Controller
{
    protected function authorizeBuyer(Request $request)
    {
        $user = $request->user();
        return $user && ($user->role ?? '') === 'buyer';
    }

    protected function deny()
    {
        return response()->json(['message' => 'Access denied. Buyer only.'], 403);
    }

    // GET /api/buyer/orders
    public function index(Request $request)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();

        // Return only orders that belong to the authenticated buyer
        $orders = Order::with('items', 'supplier')
            ->where('buyer_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $orders], 200);
    }

    // GET /api/buyer/orders/{id}
    public function show(Request $request, $id)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();

        $order = Order::with('items', 'supplier')->where('buyer_id', $request->user()->id)->find($id);
        if (! $order) return response()->json(['message' => 'Order not found'], 404);
        return response()->json(['data' => $order], 200);
    }

    // POST /api/buyer/orders
    // Buyer creates a simplified order: items with product_name and quantity only.
    public function store(Request $request)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();

        $v = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'buyer_id' => $request->user()->id,
                'supplier_id' => null, // buyer will not set supplier here
                'status' => 'Requested',
                'order_date' => now(),
                'expected_date' => null,
                'total' => 0,
            ]);

            $total = 0;
            foreach ($v['items'] as $it) {
                // save item without unit_price (nullable)
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $it['product_name'],
                    'product_sku' => $it['product_sku'] ?? null,
                    'quantity' => $it['quantity'],
                    'unit_price' => $it['unit_price'] ?? null,
                ]);
            }

            DB::commit();
            return response()->json(['status' => 'ok', 'data' => $order->load('items')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }

    // PUT /api/buyer/orders/{id}
    public function update(Request $request, $id)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();

        $order = Order::with('items')->where('buyer_id', $request->user()->id)->find($id);
        if (! $order) return response()->json(['message' => 'Order not found'], 404);
        if ($order->status !== 'Pending' && $order->status !== 'Requested') {
            return response()->json(['message' => 'Only pending/requested orders can be edited'], 400);
        }

        $v = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_name' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        DB::beginTransaction();
        try {
            $order->items()->delete();
            foreach ($v['items'] as $it) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_name' => $it['product_name'],
                    'product_sku' => $it['product_sku'] ?? null,
                    'quantity' => $it['quantity'],
                    'unit_price' => $it['unit_price'] ?? null,
                ]);
            }
            $order->save();
            DB::commit();
            return response()->json(['status'=>'ok','data' => $order->load('items')], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }

    // POST /api/buyer/orders/{id}/cancel
    public function cancel(Request $request, $id)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();

        $order = Order::where('buyer_id', $request->user()->id)->find($id);
        if (! $order) return response()->json(['message' => 'Order not found'], 404);
        if ($order->status === 'Cancelled') {
            return response()->json(['status' => 'ok', 'message' => 'Already cancelled'], 200);
        }

        // allow cancel only if not shipped/delivered
        if (in_array($order->status, ['Shipped','Delivered'])) {
            return response()->json(['message' => 'Cannot cancel an order already shipped/delivered'], 400);
        }

        $order->status = 'Cancelled';
        $order->save();
        return response()->json(['status'=>'ok','data' => $order], 200);
    }

    // POST /api/buyer/orders/{id}/duplicate
    public function duplicate(Request $request, $id)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();

        $source = Order::with('items')->where('buyer_id', $request->user()->id)->find($id);
        if (! $source) return response()->json(['message' => 'Order not found'], 404);

        DB::beginTransaction();
        try {
            $new = Order::create([
                'buyer_id' => $request->user()->id,
                'supplier_id' => $source->supplier_id,
                'status' => 'Requested',
                'order_date' => now(),
                'expected_date' => $source->expected_date,
                'total' => 0,
            ]);
            foreach ($source->items as $it) {
                OrderItem::create([
                    'order_id' => $new->id,
                    'product_name' => $it->product_name,
                    'product_sku' => $it->product_sku,
                    'quantity' => $it->quantity,
                    'unit_price' => $it->unit_price,
                ]);
            }
            DB::commit();
            return response()->json(['status'=>'ok','data' => $new->load('items')], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }

    // GET /api/buyer/orders/{id}/status
    public function status(Request $request, $id)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();

        $order = Order::where('buyer_id', $request->user()->id)->find($id);
        if (! $order) return response()->json(['message' => 'Order not found'], 404);
        return response()->json(['status' => $order->status, 'delivery_date' => $order->delivery_date ?? null], 200);
    }

    // GET /api/buyer/orders/summary
    public function summary(Request $request)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();

        $buyerId = $request->user()->id;
        $total = Order::where('buyer_id', $buyerId)->count();
        $overdue = Order::where('buyer_id', $buyerId)->where('expected_date', '<', now())->whereNotIn('status', ['Delivered','Cancelled'])->count();
        $pending = Order::where('buyer_id', $buyerId)->where('status', 'Requested')->count();

        return response()->json([
            'data' => ['total' => $total, 'overdue' => $overdue, 'pending' => $pending]
        ], 200);
    }
}
