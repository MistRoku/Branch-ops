<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('receipt_footer');
            $table->string('accent_color', 20)->default('#2563eb')->after('logo_path');
        });

        Schema::table('stock_levels', function (Blueprint $table) {
            $table->string('location', 100)->nullable()->after('quantity');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->string('fulfillment', 20)->default('pickup')->after('payment_reference');
            $table->string('delivery_address')->nullable()->after('fulfillment');
            $table->decimal('delivery_fee', 10, 2)->default(0)->after('delivery_address');
            $table->json('payments')->nullable()->after('tendered_amount');
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('loyalty_points')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('quote_number')->unique();
            $table->enum('status', ['draft', 'sent', 'accepted', 'expired', 'converted'])->default('draft');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('converted_sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);
            $table->timestamps();
        });

        Schema::create('goods_received', function (Blueprint $table) {
            $table->id();
            $table->string('grv_number', 50)->unique();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('received_by')->constrained('users')->cascadeOnDelete();
            $table->json('items');
            $table->string('delivery_notes')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_received');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
        Schema::dropIfExists('customers');
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['customer_id', 'fulfillment', 'delivery_address', 'delivery_fee', 'payments']);
        });
        Schema::table('stock_levels', function (Blueprint $table) {
            $table->dropColumn('location');
        });
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'accent_color']);
        });
    }
};
