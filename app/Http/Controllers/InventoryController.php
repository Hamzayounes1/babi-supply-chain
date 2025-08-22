<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreInventoryRequest;

class InventoryController extends Controller
{
    public function index(): JsonResponse
    {
        $records = Inventory::with(['warehouse', 'product'])->get();

        return response()->json($records);
    }

    public function store(StoreInventoryRequest $request): JsonResponse
    {
        $inventory = Inventory::create($request->validated());
        $inventory->load('warehouse', 'product');

        return response()->json($inventory, 201);
    }
}