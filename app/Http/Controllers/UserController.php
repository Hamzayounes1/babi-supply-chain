<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{



    // …

    public function show(User $user)
    {
        return response()->json([
            'data' => $user
        ], 200);
    }

    // …

    /**
     * Display a listing of users.
     */
    public function index()
    {
        return response()->json(User::all(), 200);
    }

    /**
     * Store a newly created user in storage.
     */


public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'role' => 'required|string|exists:roles,name',
    ]);

    // Create user
    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
    ]);

    // Assign role via Spatie
    $user->assignRole($validated['role']);

    return response()->json([
        'message' => 'User created successfully',
        'data' => $user->load('roles'),
    ], 201);
}


public function update(Request $request, User $user)
{
    $validated = $request->validate([
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
        'password' => 'nullable|string|min:8|confirmed',
        'role' => 'nullable|string|exists:roles,name',
    ]);

    $user->update([
        'name' => $validated['name'] ?? $user->name,
        'email' => $validated['email'] ?? $user->email,
        'password' => !empty($validated['password']) ? Hash::make($validated['password']) : $user->password,
    ]);

    if (!empty($validated['role'])) {
        $user->syncRoles([$validated['role']]); // replaces old roles
    }

    return response()->json([
        'message' => 'User updated successfully',
        'data' => $user->load('roles'),
    ]);
}


    /**
     * Display the specified user.
     */


    /**
     * Update the specified user in storage.
     */


    /**
     * Remove the specified user from storage.
     */
public function destroy(User $user)
{
    $user->delete();
    return response()->json(['message' => 'User deleted successfully'], 200);
}

}