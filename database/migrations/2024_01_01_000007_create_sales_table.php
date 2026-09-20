<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // cashier
            $table->string('invoice_number')->unique();
            $table->enum('status', ['completed', 'refunded', 'void'])->default('completed');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->enum('payment_method', ['cash', 'card', 'mobile', 'credit'])->default('cash');
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at');
            $table->timestamps();
            
            $table->index(['branch_id', 'completed_at']);
            $table->index(['user_id', 'completed_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
