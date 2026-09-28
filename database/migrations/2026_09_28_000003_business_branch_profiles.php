<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('company')->nullable();
            $table->string('business_name');
            $table->string('business_type', 100)->nullable();
            $table->string('location')->nullable();
            $table->string('country', 100)->default('South Africa');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('register_number', 100)->nullable();
            $table->string('vat_number', 100)->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('use_logo_in_invoice')->default(true);
            $table->string('currency', 10)->default('ZAR');
            $table->string('base_currency', 10)->default('ZAR');
            $table->string('fiscal_year', 20)->nullable();
            $table->string('timezone', 100)->default('Africa/Johannesburg');
            $table->string('language', 20)->default('en');
            $table->string('date_format', 30)->default('d M Y');
            $table->timestamps();
        });

        Schema::table('branches', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('branches')->nullOnDelete();
            $table->string('website')->nullable()->after('email');
            $table->string('address_line1')->nullable()->after('address');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('city', 100)->nullable()->after('address_line2');
            $table->string('postal_code', 20)->nullable()->after('city');
            $table->string('country', 100)->default('South Africa')->after('postal_code');
            $table->string('province', 100)->nullable()->after('country');
            $table->string('transaction_series', 20)->nullable()->after('code');
            $table->json('warehouses')->nullable()->after('transaction_series');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'parent_id', 'website', 'address_line1', 'address_line2',
                'city', 'postal_code', 'country', 'province',
                'transaction_series', 'warehouses',
            ]);
        });
        Schema::dropIfExists('business_profiles');
    }
};
