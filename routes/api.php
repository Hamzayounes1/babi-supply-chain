<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;

Route::apiResource('suppliers', SupplierController::class);
Route::get('suppliers', [SupplierController::class, 'index']);
Route::apiResource('suppliers', SupplierController::class);

Route::get('products', [ProductController::class, 'index']);
Route::post('products', [ProductController::class, 'store']);
Route::put('products/{id}', [ProductController::class, 'update']);
Route::delete('products/{id}', [ProductController::class, 'destroy']);


Route::get('products/{id}', [ProductController::class, 'show'])
     ->where('id', '[0-9]+');

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


Route::delete('users/{user}', [UserController::class, 'destroy']);

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