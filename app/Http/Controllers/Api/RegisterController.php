<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        // 1. Validate
        $data = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        // 2. Create user (password hash happens via mutator)
        $user = User::create($data);

        // 3. Return JSON
        return response()->json([
            'message' => 'Registration successful',
            'user'    => $user
        ], 201);
    }
}