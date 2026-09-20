<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['sale', 'purchase', 'transfer_in', 'transfer_out', 'adjustment', 'stock_take']);
            $table->integer('quantity_change'); // positive or negative
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('reference_type')->nullable(); // App\Models\Sale, App\Models\PurchaseOrder, etc.
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['product_id', 'branch_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('type');
        });

        // Partition by month for large datasets (MySQL 8+)
        // This is done via raw SQL in a seeder or manually for production
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
