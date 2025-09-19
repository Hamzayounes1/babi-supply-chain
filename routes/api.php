<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierPerformanceController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AlertsController;
use App\Http\Controllers\InventoryForecastController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\api\BuyerOrderController;
use App\Http\Controllers\api\BuyerContractController;
use App\Http\Controllers\BuyerAlertsController;
use App\Http\Controllers\WarehouseInventoryController;
use App\Http\Controllers\api\WarehouseReceiptController;
use App\Http\Controllers\api\WarehouseShipmentController;
use App\Http\Controllers\api\ReceiptController;
use App\Http\Controllers\Api\ShipmentTrackingController;



// ----------------- Buyer endpoints (inside auth:sanctum group) -----------------

// Buyer Contracts (also alias to /contract
// Buyer-specific routes (requires authenticated user via sanctum)
Route::prefix('buyer')->middleware('auth:sanctum')->group(function () {
    Route::get('orders', [BuyerOrderController::class, 'index']);
    Route::get('orders/{id}', [BuyerOrderController::class, 'show']);
    Route::post('orders', [BuyerOrderController::class, 'store']);
    Route::put('orders/{id}', [BuyerOrderController::class, 'update']);
    Route::post('orders/{id}/cancel', [BuyerOrderController::class, 'cancel']);
    Route::post('orders/{id}/duplicate', [BuyerOrderController::class, 'duplicate']);
    Route::get('orders/{id}/status', [BuyerOrderController::class, 'status']);
    Route::get('orders/summary', [BuyerOrderController::class, 'summary']);

    // Contracts CRUD for buyers
    Route::get('contracts', [BuyerContractController::class, 'index']);
    Route::get('contracts/{id}', [BuyerContractController::class, 'show']);
    Route::post('contracts', [BuyerContractController::class, 'store']);
    Route::put('contracts/{id}', [BuyerContractController::class, 'update']);
    Route::delete('contracts/{id}', [BuyerContractController::class, 'destroy']);

    // Alerts (buyer)
    Route::get('alerts', [App\Http\Controllers\BuyerAlertsController::class, 'index']);

    // Orders summary / status endpoint (for polling)
    Route::get('orders/{id}/status', [App\Http\Controllers\Api\OrderController::class, 'status']);
    Route::get('orders/summary', [App\Http\Controllers\Api\OrderController::class, 'summary']);
    Route::get('buyer-alerts', [\App\Http\Controllers\BuyerAlertsController::class, 'index']);

});


// alias /buyer-alerts



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Public authentication and resource endpoints first, then protected.
|
*/

// -------------------- Public auth --------------------
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('reset-password', [AuthController::class, 'resetPassword']);

// -------------------- Public resource reads --------------------
Route::get('products', [ProductController::class, 'index']);
Route::get('products/{id}', [ProductController::class, 'show'])->whereNumber('id');

// inside Route::prefix('inventory')->group(...)
Route::post('/update', [InventoryController::class, 'updateStock']);
// inside your auth:sanctum group:
Route::get('warehouse/stocks', [\App\Http\Controllers\WarehouseInventoryController::class, 'index']);

// Warehouses & warehouse inventory (warehouse manager access enforced inside controller)
Route::get('warehouses', [WarehouseInventoryController::class, 'index']);
Route::get('warehouses/{id}/inventories', [WarehouseInventoryController::class, 'inventories']);

// Update a single inventory stock (warehouse manager)
Route::put('inventories/{id}/stock', [WarehouseInventoryController::class, 'updateStock']);

// Update an inventory location
Route::put('inventories/{id}/location', [WarehouseInventoryController::class, 'updateLocation']);
Route::get('/inventory/warehouses', [InventoryController::class, 'warehouses']);


Route::post('warehouses/{id}/assign-inventory', [WarehouseInventoryController::class, 'assignInventoryToWarehouse']);
Route::get('/warehouses', [InventoryController::class, 'warehouses']);
Route::get('/products', [InventoryController::class, 'products']);


// Shipments (warehouse manager)

// Shipments (warehouse)
Route::get('shipments', [ShipmentController::class, 'index']);
Route::get('shipments/{id}', [ShipmentController::class, 'show'])->whereNumber('id');
Route::post('shipments', [ShipmentController::class, 'store']); // create shipment
Route::post('shipments/{id}/incident', [ShipmentController::class, 'reportIncident'])->whereNumber('id');


Route::get('receipts', [ReceiptController::class, 'index']);
Route::get('receipts/{id}', [ReceiptController::class, 'show']);
Route::post('receipts', [ReceiptController::class, 'store']);


// Warehouse receipts (goods received)
Route::post('warehouse/receipts', [ShipmentController::class, 'store']);
Route::get('/warehouses', [WarehouseShipmentController::class, 'warehouses']);
Route::get('/products', [WarehouseShipmentController::class, 'products']);

// Shipments
// routes/api.php (append)

Route::get('/shipments', [ShipmentController::class, 'index']);
Route::get('/shipments/{id}', [ShipmentController::class, 'show']);
Route::post('/shipments', [ShipmentController::class, 'store']);
Route::post('/shipments/{id}/cancel', [ShipmentController::class, 'cancel']); // optional
Route::post('/shipments/{id}/duplicate', [ShipmentController::class, 'duplicate']); // optional alias


Route::get('receipts', [ReceiptController::class, 'index']);
Route::get('receipts/{id}', [ReceiptController::class, 'show']);
Route::post('receipts', [ReceiptController::class, 'store']);


// For convenience (some clients call create)
Route::post('/shipments/create', [ShipmentController::class, 'store']);
Route::post('/receipts/create', [ReceiptController::class, 'store']);


// Shipments
Route::get('/shipments', [WarehouseShipmentController::class, 'index']);
Route::post('/shipments', [WarehouseShipmentController::class, 'store']);

// Optional: Fetch products & warehouses for dropdowns
Route::get('/warehouses', [WarehouseShipmentController::class, 'warehouses']);
Route::get('/products', [WarehouseShipmentController::class, 'products']);


// Optionally expose search/filter endpoints as needed




Route::get('inventories', [InventoryController::class, 'index']);
Route::post('inventories', [InventoryController::class, 'store']);

// -------------------- Protected routes (require Sanctum) --------------------
Route::middleware('auth:sanctum')->group(function () {

    // --- Suppliers CRUD (SCM-only inside controller) ---
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

    // --- Global dashboard, forecast, alerts (SCM-only inside controllers) ---
    Route::get('/dashboard/global', [DashboardController::class, 'global']);

    Route::get('/forecast/inventory', [InventoryForecastController::class, 'inventory']);

    Route::get('/alerts', [AlertsController::class, 'index']);

    // --- Inventories resource (SCM-only inside controller) ---
    Route::apiResource('inventories', InventoryController::class);

    // Current user profile + logout
    Route::get('user', [AuthController::class, 'profile']);
    Route::post('logout', [AuthController::class, 'logout']);

    // -------- Admin user management (role check inside controller) --------
    Route::prefix('admin')->group(function () {
        Route::get('users', [AdminUserController::class, 'index']);
        Route::post('users', [AdminUserController::class, 'store']);
        Route::patch('users/{user}', [AdminUserController::class, 'update']);
        Route::delete('users/{user}', [AdminUserController::class, 'destroy']);
        Route::get('reports', [ReportController::class, 'index']);
    });

    // Supplier performance report
    Route::get('suppliers/{id}/performance', [SupplierPerformanceController::class, 'show']);

    // -------- Orders (role checks inside controller) --------

    // -------- Inventory (role checks inside controller) --------
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::post('/update', [InventoryController::class, 'updateStock']);
        Route::get('/locations', [InventoryController::class, 'locations']);
    });

    // -------- Shipments (role checks inside controller) --------
    Route::prefix('shipments')->group(function () {
        Route::get('/', [ShipmentController::class, 'index']);
        Route::get('/{id}', [ShipmentController::class, 'show'])->whereNumber('id');
        Route::post('/', [ShipmentController::class, 'create']);
        Route::patch('/{id}', [ShipmentController::class, 'update']);
        Route::post('/{id}/incident', [ShipmentController::class, 'reportIncident']);
        Route::get('/incidents', [ShipmentTrackingController::class,'incidentsIndex']);
    });




Route::get('receipts', [ReceiptController::class, 'index']);
Route::get('receipts/{id}', [ReceiptController::class, 'show']);
Route::post('receipts', [ReceiptController::class, 'store']);


    // -------- Protected user CRUD --------
    Route::apiResource('users', UserController::class)->only(['index','show','update','destroy']);
});
