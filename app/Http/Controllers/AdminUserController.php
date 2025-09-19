<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserController extends Controller
{
    /**
     * Check if a user has any of the given roles.
     */
    protected function userHasAnyRole(User $user, array $roles): bool
    {
        // Use 'role' column (array of strings) for simplicity
        return in_array($user->role, $roles);
    }

    /**
     * Return a standardized 403 response
     */
    protected function forbiddenResponse()
    {
        return response()->json(['message' => 'Forbidden'], 403);
    }

    // GET /admin/users
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $this->userHasAnyRole($user, ['administrator'])) {
            return $this->forbiddenResponse();
        }

        return response()->json(User::all());
    }

    // POST /admin/users
    public function store(Request $request)
    {
        $user = $request->user();
        if (! $this->userHasAnyRole($user, ['administrator'])) {
            return $this->forbiddenResponse();
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|string',
        ]);

        $new = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
            'active'   => $request->filled('active') ? (bool)$request->active : true,
        ]);

        return response()->json($new, Response::HTTP_CREATED);
    }

    // PATCH /admin/users/{user}
    public function update(Request $request, User $user)
    {
        $actor = $request->user();
        if (! $this->userHasAnyRole($actor, ['administrator'])) {
            return $this->forbiddenResponse();
        }

        $validated = $request->validate([
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role'     => 'nullable|string',
            'active'   => 'nullable|boolean',
        ]);

        if (isset($validated['name'])) $user->name = $validated['name'];
        if (isset($validated['email'])) $user->email = $validated['email'];
        if (!empty($validated['password'])) $user->password = Hash::make($validated['password']);
        if (isset($validated['role'])) $user->role = $validated['role'];
        if (array_key_exists('active', $validated)) $user->active = (bool)$validated['active'];

        $user->save();

        return response()->json($user);
    }

    // DELETE /admin/users/{user}
    public function destroy(Request $request, User $user)
    {
        $actor = $request->user();
        if (! $this->userHasAnyRole($actor, ['administrator'])) {
            return $this->forbiddenResponse();
        }

        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
