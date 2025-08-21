<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Return a list of all products.
     */
    public function index(): JsonResponse
    {
        $products = Product::all();

        return response()->json([
            'data' => $products
        ], 200);
    }

    public function show($id)
{
    $product = Product::findOrFail($id);

    return response()->json($product, 200);
}

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'sku' => 'required|string|unique:products,sku',
        'description' => 'nullable|string',
        'price' => 'required|numeric|min:0',
    ]);

    $product = Product::create($validated);

    return response()->json([
        'message' => 'Product created successfully',
        'data' => $product
    ], 201);
}

public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $validated = $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'sku' => 'sometimes|required|string|unique:products,sku,' . $id,
        'description' => 'nullable|string',
        'price' => 'sometimes|required|numeric|min:0',
    ]);

    $product->update($validated);

    return response()->json([
        'message' => 'Product updated successfully',
        'data' => $product
    ], 200);
}

public function destroy($id)
{
    $product = Product::findOrFail($id);
    $product->delete();

    return response()->json([
        'message' => 'Product deleted successfully'
    ], 200);
}
}
