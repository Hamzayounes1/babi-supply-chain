<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Contract; // create model/migration if not exists
use Illuminate\Support\Facades\Validator;

class BuyerContractController extends Controller
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

    public function index(Request $request)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();
        $contracts = Contract::where('buyer_id', $request->user()->id)->get();
        return response()->json(['data' => $contracts], 200);
    }

    public function show(Request $request, $id)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();
        $c = Contract::where('buyer_id', $request->user()->id)->find($id);
        if (! $c) return response()->json(['message' => 'Not found'], 404);
        return response()->json(['data' => $c], 200);
    }

    public function store(Request $request)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();

        $v = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'value' => 'nullable|numeric',
            'terms' => 'nullable|string',
        ]);

        if ($v->fails()) return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);

        $c = Contract::create(array_merge($v->validated(), ['buyer_id' => $request->user()->id]));
        return response()->json(['data' => $c], 201);
    }

    public function update(Request $request, $id)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();

        $c = Contract::where('buyer_id', $request->user()->id)->find($id);
        if (! $c) return response()->json(['message' => 'Not found'], 404);

        $v = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'value' => 'nullable|numeric',
            'terms' => 'nullable|string',
        ]);

        if ($v->fails()) return response()->json(['message' => 'Validation failed', 'errors' => $v->errors()], 422);

        $c->update($v->validated());
        return response()->json(['data' => $c], 200);
    }

    public function destroy(Request $request, $id)
    {
        if (! $this->authorizeBuyer($request)) return $this->deny();
        $c = Contract::where('buyer_id', $request->user()->id)->find($id);
        if (! $c) return response()->json(['message' => 'Not found'], 404);
        $c->delete();
        return response()->json(['message' => 'Deleted'], 200);
    }
}
