<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Supplier;


class SupplierController extends Controller
{
    public function index()
{
    return Supplier::all();
}

public function store(Request $request)
{
    $data = $request->validate([
        'name'          => 'required|string|max:255',
        'contact_email' => 'nullable|email',
        'phone'         => 'nullable|string|max:20',
        'address'       => 'nullable|string',
    ]);

    $supplier = Supplier::create($data);

    return response($supplier, 201);
}
}
