<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates tables for cash drawer management, refunds, and enhanced product tracking.
     */
    public function up(): void
    {
        // Cash drawers table - tracks physical drawer sessions
        Schema::create('cash_drawers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('identifier')->unique();
            $table->enum('status', ['open', 'closed', 'suspended'])->default('closed');
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->decimal('closing_balance', 10, 2)->nullable();
            $table->decimal('expected_balance', 10, 2)->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_transaction_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['branch_id', 'status']);
            $table->index('identifier');
        });

        // Cash movements table - tracks individual cash transactions
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_drawer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['open', 'close', 'payout', 'payin', 'transfer', 'adjustment']);
            $table->decimal('amount', 10, 2);
            $table->decimal('variance', 10, 2)->nullable();
            $table->text('description');
            $table->string('authorization_code')->nullable();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('reference'); // Polymorphic relation to sale, refund, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for querying
            $table->index(['cash_drawer_id', 'type']);
            $table->index(['branch_id', 'created_at']);
            $table->index('type');
        });

        // Sale refunds table - tracks full and partial refunds
        Schema::create('sale_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('refund_type', ['full', 'partial', 'item']);
            $table->decimal('refund_amount', 10, 2);
            $table->text('refund_reason');
            $table->string('authorization_code')->nullable();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->enum('payment_method', ['cash', 'card', 'original'])->default('original');
            $table->string('card_last_four', 4)->nullable();
            $table->foreignId('cash_drawer_id')->nullable()->constrained('cash_drawers')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for filtering
            $table->index(['sale_id', 'status']);
            $table->index(['branch_id', 'created_at']);
            $table->index('status');
        });

        // Update products table with recall and branch tracking
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('barcode_type')->nullable()->after('barcode');
            $table->renameColumn('selling_price', 'price');
            $table->integer('stock_quantity')->default(0)->after('tax_rate');
            $table->integer('low_stock_threshold')->default(5)->after('stock_quantity');
            $table->enum('product_type', ['physical', 'digital', 'service'])->default('physical')->after('low_stock_threshold');
            $table->boolean('is_recalled')->default(false)->after('product_type');
            $table->text('recall_reason')->nullable()->after('is_recalled');
            $table->timestamp('recalled_at')->nullable()->after('recall_reason');
            $table->string('recall_batch')->nullable()->after('recalled_at');
            $table->json('metadata')->nullable()->after('recall_batch');
            $table->dropColumn(['unit_of_measure', 'reorder_level', 'is_active', 'attributes']);
        });

        // Update sales table with enhanced tracking
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('authorization_code')->nullable()->after('card_last_four');
            $table->enum('status', ['completed', 'voided', 'refunded_full', 'refunded_partial', 'recalled'])->default('completed')->after('payment_method');
            $table->text('void_reason')->nullable()->after('status');
            $table->foreignId('voided_by')->nullable()->after('void_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->after('voided_by');
            $table->text('recall_reason')->nullable()->after('voided_at');
            $table->foreignId('recalled_by')->nullable()->after('recall_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('recalled_at')->nullable()->after('recalled_by');
            $table->foreignId('cash_drawer_id')->nullable()->after('recalled_at')->constrained('cash_drawers')->nullOnDelete();
            $table->json('metadata')->nullable()->after('cash_drawer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'customer_id', 'authorization_code', 'status', 'void_reason', 'voided_by',
                'voided_at', 'recall_reason', 'recalled_by', 'recalled_at', 'cash_drawer_id', 'metadata'
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'branch_id', 'barcode_type', 'stock_quantity', 'low_stock_threshold',
                'product_type', 'is_recalled', 'recall_reason', 'recalled_at', 'recall_batch', 'metadata'
            ]);
            $table->renameColumn('price', 'selling_price');
            $table->string('unit_of_measure')->nullable();
            $table->integer('reorder_level')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('attributes')->nullable();
        });

        Schema::dropIfExists('sale_refunds');
        Schema::dropIfExists('cash_movements');
        Schema::dropIfExists('cash_drawers');
    }
};
