<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    /**
     * Handle new user registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Create user
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => ($request->password),
        ]);

        // Optionally send email verification
        $user->sendEmailVerificationNotification();

        // Create sanctum token
        $token = $user->createToken('api_token')->plainTextToken;

        // Return JSON response
        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }
}