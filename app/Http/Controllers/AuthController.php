<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * Prepare user data for API responses including roles and permissions.
     */
    protected function userPayload(User $user): array
    {
        $data = $user->toArray();

        // Roles array (from column if Spatie not installed)
        if (method_exists($user, 'getRoleNames')) {
            try {
                $roles = $user->getRoleNames();
                $data['roles'] = is_array($roles) ? $roles : $roles->toArray();
            } catch (\Throwable $e) {
                $data['roles'] = isset($data['role']) ? [$data['role']] : [];
            }
        } else {
            $data['roles'] = isset($data['role']) && $data['role'] !== null ? [(string) $data['role']] : [];
        }

        // Permissions array
        if (method_exists($user, 'getAllPermissions')) {
            try {
                $perms = $user->getAllPermissions();
                $data['permissions'] = is_array($perms) ? $perms : $perms->pluck('name')->toArray();
            } catch (\Throwable $e) {
                $data['permissions'] = [];
            }
        } elseif (!array_key_exists('permissions', $data)) {
            $data['permissions'] = [];
        }

        return $data;
    }

    // Register
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string', // optional role during registration
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'active' => true,
            'role' => $validated['role'] ?? 'user',
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ], Response::HTTP_CREATED);
    }

    // Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    // Logout
    public function logout(Request $request)
    {
        if ($request->user()) {
            $current = $request->user()->currentAccessToken();
            if ($current) {
                $current->delete();
            }
        }

        return response()->json(['message' => 'Logged out']);
    }

    // Profile
    public function profile(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(null, 204);
        }

        return response()->json($this->userPayload($user));
    }

    // Forgot password (dev: return token)
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $user = User::where('email', $request->email)->firstOrFail();
        $broker = Password::broker();

        try {
            $token = $broker->createToken($user);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create reset token'], 500);
        }

        return response()->json([
            'message' => 'Reset link sent (dev). Use token to reset.',
            'token' => $token,
        ]);
    }

    // Reset password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset successfully']);
        }

        return response()->json(['message' => 'Failed to reset password'], 500);
    }
}
