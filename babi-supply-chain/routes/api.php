<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Public: user registration.
| Protected (Sanctum): index, show, update, destroy.
|
*/

// 1) Public registration (no auth)
Route::post('users', [UserController::class, 'store'])->name('users.store');
Route::get('ping', fn() => response()->json(['pong' => true]));

// 2) Protected CRUD (index, show, update, destroy)
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('users', UserController::class)
         ->only(['index', 'show', 'update', 'destroy'])
         ->names([
             'index'   => 'users.index',
             'show'    => 'users.show',
             'update'  => 'users.update',
             'destroy' => 'users.destroy',
         ]);
});
// Registration
Route::post('register', [AuthController::class, 'register']);

// Login
Route::post('/login', [AuthController::class, 'login']);
Route::get('users/{user}', [UserController::class, 'show']);


Route::get('/users', [UserController::class, 'index']);

// Protected logout/user routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', function (Request $request) {
        return $request->user();
    });
});

// Add your custom API routes below