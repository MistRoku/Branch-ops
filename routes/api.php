<?php

use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\PromotionController;
use App\Http\Controllers\Api\V1\SalesController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - BranchOps Platform
|--------------------------------------------------------------------------
|
| All API routes are prefixed with /api/v1 and protected by Sanctum auth.
| Rate limiting is applied to prevent abuse.
|
*/

// Apply rate limiting to all API routes
Route::middleware(['throttle:api'])->group(function () {

    // Public endpoints (no authentication required)
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'BranchOps API is healthy',
            'timestamp' => now()->toISOString(),
        ]);
    });
});

// Protected API routes (authentication required).
// Canonical paths are /api/v1/* (per README, POS frontend and SecurityTest).
// Unprefixed /api/* aliases are kept for older callers (SaleCheckoutTest,
// PermissionMatrixTest) so both work regardless of which base the client uses.
$registerProtectedApiRoutes = function () {
    /*
    |--------------------------------------------------------------------------
    | Dashboard Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/activity', [DashboardController::class, 'activityFeed']);

    /*
    |--------------------------------------------------------------------------
    | Product Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);           // List products
        Route::post('/', [ProductController::class, 'store']);          // Create product
        Route::get('/low-stock', [ProductController::class, 'lowStock']); // Low stock alert
        Route::get('/{id}', [ProductController::class, 'show']);        // Get product
        Route::put('/{id}', [ProductController::class, 'update']);      // Update product
        Route::delete('/{id}', [ProductController::class, 'destroy']);  // Delete product
    });

    /*
    |--------------------------------------------------------------------------
    | Inventory Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventory')->group(function () {
        Route::get('/', [InventoryController::class, 'index']);              // List stock levels
        Route::post('/adjust', [InventoryController::class, 'adjust']);      // Adjust stock
        Route::get('/movements', [InventoryController::class, 'movements']); // Stock movement history
        Route::get('/valuation', [InventoryController::class, 'valuation']); // Inventory valuation
        Route::get('/all-movements', [InventoryController::class, 'allMovements']); // All movements
    });

    /*
    |--------------------------------------------------------------------------
    | Supplier Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('suppliers')->group(function () {
        Route::get('/', [SupplierController::class, 'index']);           // List suppliers
        Route::post('/', [SupplierController::class, 'store']);          // Create supplier
        Route::get('/{id}', [SupplierController::class, 'show']);        // Get supplier
        Route::put('/{id}', [SupplierController::class, 'update']);      // Update supplier
        Route::delete('/{id}', [SupplierController::class, 'destroy']);  // Delete supplier
    });

    /*
    |--------------------------------------------------------------------------
    | Sales Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('sales')->group(function () {
        Route::get('/', [SalesController::class, 'index']);           // List sales
        Route::post('/', [SalesController::class, 'store']);          // Record sale
        Route::get('/stats', [SalesController::class, 'stats']);      // Sales statistics
        Route::get('/{id}', [SalesController::class, 'show']);        // Get sale
    });

    /*
    |--------------------------------------------------------------------------
    | Search Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/search', [SearchController::class, 'index']);  // Global search

    /*
    |--------------------------------------------------------------------------
    | Promotion Routes (specials + coupons for the POS)
    |--------------------------------------------------------------------------
    */
    Route::get('/specials/active', [PromotionController::class, 'specials']);
    Route::post('/coupons/validate', [PromotionController::class, 'validateCoupon']);

    /*
    |--------------------------------------------------------------------------
    | Sale Refund + Void Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/sales/{id}/refund', [SalesController::class, 'refund']);
    Route::post('/sales/{id}/void', [SalesController::class, 'void']);
};

Route::middleware(['auth:sanctum', 'throttle:120,1'])->group($registerProtectedApiRoutes);
Route::middleware(['auth:sanctum', 'throttle:120,1'])->prefix('v1')->group($registerProtectedApiRoutes);
