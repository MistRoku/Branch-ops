<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // daily_sales, inventory_valuation, etc.
            $table->enum('format', ['pdf', 'excel', 'csv'])->default('pdf');
            $table->json('recipients'); // array of emails
            $table->json('filters')->nullable();
            $table->enum('frequency', ['daily', 'weekly', 'monthly']);
            $table->time('send_at')->default('08:00:00');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'is_active']);
            $table->index('frequency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_schedules');
    }
};
