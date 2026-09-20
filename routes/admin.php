<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\TransferController;
use App\Http\Controllers\Admin\StockTakeController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Web Routes - BranchOps Platform
|--------------------------------------------------------------------------
|
| All admin routes are prefixed with /admin and protected by auth middleware.
| These routes serve the Blade-based admin interface.
|
*/

Route::middleware(['auth', 'verified'])->group(function () {
    
    /*
    |--------------------------------------------------------------------------
    | Admin Dashboard
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('admin.dashboard');
    
    /*
    |--------------------------------------------------------------------------
    | Product Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('products')->name('admin.products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/create', [ProductController::class, 'create'])->name('create');
        Route::post('/', [ProductController::class, 'store'])->name('store');
        Route::get('/{id}', [ProductController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ProductController::class, 'update'])->name('update');
        Route::delete('/{id}', [ProductController::class, 'destroy'])->name('destroy');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Inventory Management Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventory')->name('admin.inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/movements', [InventoryController::class, 'movements'])->name('movements');
        Route::get('/valuation', [InventoryController::class, 'valuation'])->name('valuation');
        Route::get('/adjust', [InventoryController::class, 'showAdjustmentForm'])->name('adjust.form');
        Route::post('/adjust', [InventoryController::class, 'adjust'])->name('adjust.store');
        Route::get('/product/{productId}/movements', [InventoryController::class, 'productMovements'])
            ->name('product-movements');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Supplier Management Routes (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::prefix('suppliers')->name('admin.suppliers.')->group(function () {
        Route::get('/', [AdminSupplierController::class, 'index'])->name('index');
        Route::get('/create', [AdminSupplierController::class, 'create'])->name('create');
        Route::post('/', [AdminSupplierController::class, 'store'])->name('store');
        Route::get('/{id}', [AdminSupplierController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [AdminSupplierController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminSupplierController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminSupplierController::class, 'destroy'])->name('destroy');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Transfer Management Routes (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::prefix('transfers')->name('admin.transfers.')->group(function () {
        Route::get('/', [TransferController::class, 'index'])->name('index');
        Route::get('/create', [TransferController::class, 'create'])->name('create');
        Route::post('/', [TransferController::class, 'store'])->name('store');
        Route::get('/{id}', [TransferController::class, 'show'])->name('show');
        Route::put('/{id}/approve', [TransferController::class, 'approve'])->name('approve');
        Route::put('/{id}/reject', [TransferController::class, 'reject'])->name('reject');
        Route::put('/{id}/receive', [TransferController::class, 'receive'])->name('receive');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Stock Take Management Routes (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::prefix('stock-takes')->name('admin.stock-takes.')->group(function () {
        Route::get('/', [StockTakeController::class, 'index'])->name('index');
        Route::get('/create', [StockTakeController::class, 'create'])->name('create');
        Route::post('/', [StockTakeController::class, 'store'])->name('store');
        Route::get('/{id}', [StockTakeController::class, 'show'])->name('show');
        Route::put('/{id}', [StockTakeController::class, 'update'])->name('update');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Purchase Order Management Routes (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::prefix('purchase-orders')->name('admin.purchase-orders.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{id}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::get('/{id}/receive', function (int $id) { $po = \App\Models\PurchaseOrder::with('items.product')->findOrFail($id); return view('admin.purchase-orders.receive', compact('po')); })->name('receive');
        Route::put('/{id}/send', [PurchaseOrderController::class, 'send'])->name('send');
        Route::post('/{id}/receive', [PurchaseOrderController::class, 'receive'])->name('receive.store');
        Route::put('/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->name('cancel');
    });
    
    /*
    |--------------------------------------------------------------------------
    | User Management Routes (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->name('admin.users.')->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/create', [AdminUserController::class, 'create'])->name('create');
        Route::post('/', [AdminUserController::class, 'store'])->name('store');
        Route::get('/{id}', [AdminUserController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [AdminUserController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminUserController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminUserController::class, 'destroy'])->name('destroy');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Report Management Routes (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->name('admin.reports.')->group(function () {
        // Route::get('/', [ReportController::class, 'index'])->name('index');
        // Route::get('/{id}', [ReportController::class, 'show'])->name('show');
        // Route::get('/{id}/download', [ReportController::class, 'download'])->name('download');
        // Route::get('/schedules', [ReportController::class, 'schedules'])->name('schedules');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Document Management Routes (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::prefix('documents')->name('admin.documents.')->group(function () {
        // Route::get('/', [DocumentController::class, 'index'])->name('index');
        // Route::post('/', [DocumentController::class, 'store'])->name('store');
        // Route::get('/{id}', [DocumentController::class, 'show'])->name('show');
        // Route::get('/{id}/download', [DocumentController::class, 'download'])->name('download');
        // Route::delete('/{id}', [DocumentController::class, 'destroy'])->name('destroy');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Notification Management Routes (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::prefix('notifications')->name('admin.notifications.')->group(function () {
        // Route::get('/', [NotificationController::class, 'index'])->name('index');
        // Route::put('/{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
        // Route::put('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Audit Log Management Routes (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::prefix('audit-logs')->name('admin.audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/{id}', [AuditLogController::class, 'show'])->name('show');
    });
    
    /*
    |--------------------------------------------------------------------------
    | Settings Management Routes (Placeholder)
    |--------------------------------------------------------------------------
    */
    Route::prefix('settings')->name('admin.settings.')->group(function () {
        // Route::get('/', [SettingsController::class, 'index'])->name('index');
        // Route::get('/branch', [SettingsController::class, 'branch'])->name('branch');
        // Route::put('/branch', [SettingsController::class, 'updateBranch'])->name('branch.update');
        // Route::get('/tax', [SettingsController::class, 'tax'])->name('tax');
        // Route::put('/tax', [SettingsController::class, 'updateTax'])->name('tax.update');
        // Route::get('/receipt', [SettingsController::class, 'receipt'])->name('receipt');
        // Route::put('/receipt', [SettingsController::class, 'updateReceipt'])->name('receipt.update');
        // Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
        // Route::put('/profile', [SettingsController::class, 'updateProfile'])->name('profile.update');
        // Route::put('/password', [SettingsController::class, 'changePassword'])->name('password.change');
    });
});
