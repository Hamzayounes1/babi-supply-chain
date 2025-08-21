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
    $data = $request->validate([
        'name'                  => 'required|string|max:255',
        'email'                 => 'required|email|unique:users,email',
        'password'              => 'required|string|min:8|confirmed',
    ]);

    $data['password'] = ($data['password']);

    $user = User::create($data);

    return response()->json([
        'message' => 'User created successfully',
        'data'    => $user
    ], 201);
}

public function update(Request $request, User $user)
{
    $data = $request->validate([
        'name'     => 'sometimes|required|string|max:255',
        'email'    => 'sometimes|required|email|unique:users,email,' . $user->id,
        'password' => 'sometimes|required|string|min:8|confirmed',
    ]);

    if (isset($data['password'])) {
        $data['password'] = ($data['password']);
    } else {
        unset($data['password']);
    }

    $user->update($data);

    return response()->json([
        'message' => 'User updated successfully',
        'data'    => $user
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