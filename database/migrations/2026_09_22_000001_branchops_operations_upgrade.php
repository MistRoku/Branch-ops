<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('tip_amount', 10, 2)->default(0)->after('discount_amount');
            $table->decimal('tendered_amount', 10, 2)->nullable()->after('tip_amount');
            $table->decimal('change_amount', 10, 2)->default(0)->after('tendered_amount');
            $table->string('coupon_code', 50)->nullable()->after('payment_reference');
            $table->string('void_reason')->nullable()->after('notes');
            $table->foreignId('voided_by')->nullable()->after('void_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable()->after('voided_by');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('id_number', 50)->nullable()->after('phone');
            $table->string('emergency_contact_name')->nullable()->after('id_number');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');
            $table->string('emergency_contact_relation', 50)->nullable()->after('emergency_contact_phone');
            $table->text('notes')->nullable()->after('emergency_contact_relation');
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(15)->after('email');
            $table->string('receipt_header')->nullable()->after('tax_rate');
            $table->string('receipt_footer')->nullable()->after('receipt_header');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->foreignId('received_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('delivery_notes')->nullable()->after('notes');
            $table->string('grv_number', 50)->nullable()->unique()->after('po_number');
        });

        Schema::create('specials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('special_price', 10, 2);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('description')->nullable();
            $table->enum('type', ['percent', 'fixed'])->default('percent');
            $table->decimal('value', 10, 2);
            $table->decimal('min_total', 10, 2)->default(0);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('payee');
            $table->decimal('amount', 10, 2);
            $table->string('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('waste_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->string('uom', 50)->default('piece');
            $table->string('reason');
            $table->timestamp('logged_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_logs');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('specials');

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn(['received_by', 'delivery_notes', 'grv_number']);
        });
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'receipt_header', 'receipt_footer']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'id_number', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation', 'notes']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['tip_amount', 'tendered_amount', 'change_amount', 'coupon_code', 'void_reason', 'voided_by', 'voided_at']);
        });
    }
};
