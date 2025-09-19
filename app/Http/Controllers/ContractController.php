<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ContractController extends Controller
{

    protected function checkBuyer(Request $request)
    {
        $user = $this->resolveUser($request);
        return ($user && ($user->role ?? '') === 'buyer') ? $user : null;
    }

    public function index(Request $request)
    {
        $user = $this->checkBuyer($request);
        if (!$user) return response()->json(['message'=>'Access denied'], 403);
        // If buyer sees only his buyer-created contracts, otherwise all (here show all)
        $contracts = Contract::with('supplier')->orderBy('created_at','desc')->get();
        return response()->json(['data'=>$contracts],200);
    }

    public function show(Request $request, $id)
    {
        $user = $this->checkBuyer($request);
        if (!$user) return response()->json(['message'=>'Access denied'], 403);
        $c = Contract::with('supplier')->find($id);
        if (!$c) return response()->json(['message'=>'Not found'],404);
        return response()->json(['data'=>$c],200);
    }

    public function store(Request $request)
    {
        $user = $this->checkBuyer($request);
        if (!$user) return response()->json(['message'=>'Access denied'], 403);

        $v = Validator::make($request->all(), [
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'title' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'terms' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation failed','errors'=>$v->errors()],422);

        $data = $v->validated();
        $data['created_by'] = $user->id;
        $contract = Contract::create($data);
        return response()->json(['data'=>$contract],201);
    }

    public function update(Request $request, $id)
    {
        $user = $this->checkBuyer($request);
        if (!$user) return response()->json(['message'=>'Access denied'], 403);
        $c = Contract::find($id);
        if (!$c) return response()->json(['message'=>'Not found'],404);

        $v = Validator::make($request->all(), [
            'supplier_id' => 'nullable|integer|exists:suppliers,id',
            'title' => 'sometimes|required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'terms' => 'nullable|string',
        ]);
        if ($v->fails()) return response()->json(['message'=>'Validation failed','errors'=>$v->errors()],422);

        $c->update($v->validated());
        return response()->json(['data'=>$c],200);
    }

    public function destroy(Request $request, $id)
    {
        $user = $this->checkBuyer($request);
        if (!$user) return response()->json(['message'=>'Access denied'], 403);
        $c = Contract::find($id);
        if (!$c) return response()->json(['message'=>'Not found'],404);
        $c->delete();
        return response()->json(['message'=>'Deleted'],200);
    }
}
