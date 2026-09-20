<?php

use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\InventoryController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\SalesController;
use App\Http\Controllers\Api\V1\SearchController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

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

// Protected API routes (authentication required)
Route::middleware(['auth:sanctum'])->group(function () {
    
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
        Route::get('/{id}', [ProductController::class, 'show']);        // Get product
        Route::put('/{id}', [ProductController::class, 'update']);      // Update product
        Route::delete('/{id}', [ProductController::class, 'destroy']);  // Delete product
        Route::get('/low-stock', [ProductController::class, 'lowStock']); // Low stock alert
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
        Route::get('/{id}', [SalesController::class, 'show']);        // Get sale
        Route::get('/stats', [SalesController::class, 'stats']);      // Sales statistics
    });
    
    /*
    |--------------------------------------------------------------------------
    | Search Routes
    |--------------------------------------------------------------------------
    */
    Route::get('/search', [SearchController::class, 'index']);  // Global search
    
    /*
    |--------------------------------------------------------------------------
    | Transfer Routes (Placeholder - to be implemented)
    |--------------------------------------------------------------------------
    */
    Route::prefix('transfers')->group(function () {
        // Route::get('/', [TransferController::class, 'index']);
        // Route::post('/', [TransferController::class, 'store']);
        // Route::get('/{id}', [TransferController::class, 'show']);
        // Route::put('/{id}/approve', [TransferController::class, 'approve']);
        // Route::put('/{id}/reject', [TransferController::class, 'reject']);
        // Route::put('/{id}/receive', [TransferController::class, 'receive']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Purchase Order Routes (Placeholder - to be implemented)
    |--------------------------------------------------------------------------
    */
    Route::prefix('purchase-orders')->group(function () {
        // Route::get('/', [PurchaseOrderController::class, 'index']);
        // Route::post('/', [PurchaseOrderController::class, 'store']);
        // Route::get('/{id}', [PurchaseOrderController::class, 'show']);
        // Route::put('/{id}/send', [PurchaseOrderController::class, 'send']);
        // Route::post('/{id}/receive', [PurchaseOrderController::class, 'receive']);
        // Route::put('/{id}/cancel', [PurchaseOrderController::class, 'cancel']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Stock Take Routes (Placeholder - to be implemented)
    |--------------------------------------------------------------------------
    */
    Route::prefix('stock-takes')->group(function () {
        // Route::get('/', [StockTakeController::class, 'index']);
        // Route::post('/', [StockTakeController::class, 'store']);
        // Route::get('/{id}', [StockTakeController::class, 'show']);
        // Route::put('/{id}', [StockTakeController::class, 'update']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | User Routes (Placeholder - to be implemented)
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->group(function () {
        // Route::get('/', [UserController::class, 'index']);
        // Route::post('/', [UserController::class, 'store']);
        // Route::get('/{id}', [UserController::class, 'show']);
        // Route::put('/{id}', [UserController::class, 'update']);
        // Route::delete('/{id}', [UserController::class, 'destroy']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Report Routes (Placeholder - to be implemented)
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->group(function () {
        // Route::get('/', [ReportController::class, 'index']);
        // Route::post('/', [ReportController::class, 'store']);
        // Route::get('/{id}', [ReportController::class, 'show']);
        // Route::get('/{id}/download', [ReportController::class, 'download']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Document Routes (Placeholder - to be implemented)
    |--------------------------------------------------------------------------
    */
    Route::prefix('documents')->group(function () {
        // Route::get('/', [DocumentController::class, 'index']);
        // Route::post('/', [DocumentController::class, 'store']);
        // Route::get('/{id}', [DocumentController::class, 'show']);
        // Route::get('/{id}/download', [DocumentController::class, 'download']);
        // Route::delete('/{id}', [DocumentController::class, 'destroy']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Notification Routes (Placeholder - to be implemented)
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifications')->group(function () {
        // Route::get('/', [NotificationController::class, 'index']);
        // Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        // Route::put('/read-all', [NotificationController::class, 'markAllAsRead']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Audit Log Routes (Placeholder - to be implemented)
    |--------------------------------------------------------------------------
    */
    Route::prefix('audit-logs')->group(function () {
        // Route::get('/', [AuditLogController::class, 'index']);
        // Route::get('/{id}', [AuditLogController::class, 'show']);
        // Route::get('/export', [AuditLogController::class, 'export']);
    });
    
    /*
    |--------------------------------------------------------------------------
    | Settings Routes (Placeholder - to be implemented)
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->group(function () {
        // Route::get('/', [SettingsController::class, 'index']);
        // Route::put('/branch', [SettingsController::class, 'updateBranch']);
        // Route::put('/tax', [SettingsController::class, 'updateTax']);
        // Route::put('/receipt', [SettingsController::class, 'updateReceipt']);
        // Route::put('/profile', [SettingsController::class, 'updateProfile']);
        // Route::put('/password', [SettingsController::class, 'changePassword']);
    });
});
