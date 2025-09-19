<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Support\Facades\Validator;

class SupplierController extends Controller
{
    /**
     * Require authenticated user (Sanctum) and check role === 'supply_chain_manager'
     */
    protected function authorizeSCM(Request $request)
    {
        $user = $request->user(); // <-- Sanctum authenticated user
        if (! $user) {
            return false;
        }
        return (($user->role ?? '') === 'supply_chain_manager');
    }

    protected function denyResponse()
    {
        return response()->json(['message' => 'Access denied. Supply Chain Manager only.'], 403);
    }

    public function index(Request $request)
    {
        if (! $this->authorizeSCM($request)) return $this->denyResponse();

        $suppliers = Supplier::orderBy('name')->get();
        return response()->json(['data' => $suppliers], 200);
    }

    public function show(Request $request, $id)
    {
        if (! $this->authorizeSCM($request)) return $this->denyResponse();

        $s = Supplier::with(['orders' => function($q){ $q->latest('order_date')->limit(10); }])->find($id);
        if (! $s) return response()->json(['message' => 'Supplier not found'], 404);
        return response()->json(['data' => $s], 200);
    }

    public function store(Request $request)
    {
        if (! $this->authorizeSCM($request)) return $this->denyResponse();

        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1024',
            'rating' => 'nullable|numeric',
            'performance_score' => 'nullable|integer',
            'on_time_percentage' => 'nullable|integer',
            'last_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }

        $supplier = Supplier::create($v->validated());
        return response()->json(['data' => $supplier], 201);
    }

    public function update(Request $request, $id)
    {
        if (! $this->authorizeSCM($request)) return $this->denyResponse();

        $supplier = Supplier::find($id);
        if (! $supplier) return response()->json(['message' => 'Supplier not found'], 404);

        $v = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1024',
            'rating' => 'nullable|numeric',
            'performance_score' => 'nullable|integer',
            'on_time_percentage' => 'nullable|integer',
            'last_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        if ($v->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);
        }

        $supplier->update($v->validated());
        return response()->json(['data' => $supplier], 200);
    }

    public function destroy(Request $request, $id)
    {
        if (! $this->authorizeSCM($request)) return $this->denyResponse();

        $supplier = Supplier::find($id);
        if (! $supplier) return response()->json(['message' => 'Supplier not found'], 404);

        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted'], 200);
    }
}
